import { createServer } from 'http';
import { WebSocketServer } from 'ws';
import { createClient, AgentEvents } from '@deepgram/sdk';
import * as dotenv from 'dotenv';

dotenv.config();

const DEEPGRAM_API_KEY = process.env.DEEPGRAM_API_KEY;

if (!DEEPGRAM_API_KEY) {
  console.error('Please set your DEEPGRAM_API_KEY in the .env file');
  process.exit(1);
}

// 🎵 REAL-TIME STREAMING CONFIGURATION
// Streams audio chunks immediately for v1.03 compatibility
const AUDIO_CONFIG = {
  encoding: 'linear16' as const,
  sample_rate: 8000,  // Match FreeSWITCH codec rate (8kHz PCMU)
  channels: 1,        // Mono audio
  bytes_per_sample: 2 // 16-bit samples = 2 bytes per sample
};

const deepgram = createClient(DEEPGRAM_API_KEY);

// Create HTTP server optimized for real-time audio streaming
const server = createServer();
const wss = new WebSocketServer({ 
  server, 
  path: '/fs',
  perMessageDeflate: false,    // Critical: No compression for real-time audio
  maxPayload: 2 * 1024 * 1024, // 2MB max payload for large audio chunks
  backlog: 511                 // Increase connection queue
});

// Track active connections
const connections = new Map<string, {
  fsSocket: any;
  dgAgent: any;
  agentReady: boolean;
  startTime: number;
  audioStats: {
    totalBytes: number;
    totalFrames: number;
    lastLogTime: number;
    streamedChunks: number;
  };
}>();

wss.on('connection', async (fsSocket, req) => {
  const connectionId = `${req.socket.remoteAddress}:${req.socket.remotePort}:${Date.now()}`;
  console.log(`\n🔗 New FreeSWITCH connection: ${connectionId}`);
  console.log(`📍 URL: ${req.url}`);
  
  // 🚀 v1.0.3: Log connection headers for debugging
  console.log(`📋 Connection headers:`, req.headers);
  
  // 🚀 CRITICAL: Configure socket for real-time streaming
  try {
    (fsSocket as any)._socket?.setNoDelay?.(true);  // Disable Nagle's algorithm for immediate sending
    (fsSocket as any)._socket?.setKeepAlive?.(true, 30000); // Keep connection alive
  } catch (e) {
    // Ignore if not supported
  }

  // Create Deepgram agent connection
  const dgAgent = deepgram.agent();
  
  const connectionState = {
    fsSocket,
    dgAgent,
    agentReady: false,
    startTime: Date.now(),
    audioStats: {
      totalBytes: 0,
      totalFrames: 0,
      lastLogTime: Date.now(),
      streamedChunks: 0
    }
  };
  
  connections.set(connectionId, connectionState);



  // Setup Deepgram agent event handlers
  dgAgent.on(AgentEvents.Open, () => {
    console.log(`🤖 Deepgram agent connected`);
  });

  dgAgent.on('Welcome', (data: any) => {
    console.log(`🚀 Agent ready for conversation`);
    
    // Configure agent for optimal real-time performance
    dgAgent.configure({
      audio: {
        input: {
          encoding: AUDIO_CONFIG.encoding,
          sample_rate: AUDIO_CONFIG.sample_rate
        },
        output: {
          encoding: AUDIO_CONFIG.encoding,
          sample_rate: AUDIO_CONFIG.sample_rate,
          container: 'none'
        }
      },
      agent: {
        language: 'en',
        listen: {
          provider: {
            type: 'deepgram',
            model: 'nova-2'  // Faster than nova-3 for real-time
          }
        },
        think: {
          provider: {
            type: 'open_ai',
            model: 'gpt-4o'
          },
          prompt: 'You are a phone assistant for CallerTechnologies. Your job is to help callers in a friendly, conversational tone. Always keep responses short and natural, under 20 words. You should only provide information, answers, or support about CallerTechnologies’ offerings, which include VoIP phone system, caller insights (demographics, scoring, analytics), automated marketing (ads, follow-ups, campaigns), and sales coaching and performance tools. If a caller asks about anything outside these areas, you must politely decline. Never guess or provide unrelated info. Instead, briefly say you can’t help with that and guide the caller back to CallerTechnologies’ services. Do not make long speeches or explanations. Keep your responses quick, clear, and customer-focused.'
        },
        speak: {
          provider: {
            type: 'deepgram',
            model: 'aura-2-thalia-en'
          }
        },
        greeting: 'Hi! How can I help you?'
      }
    });
    
    connectionState.agentReady = true;
  });

  dgAgent.on('SettingsApplied', (data: any) => {
    console.log(`⚙️  Agent configuration applied`);
  });

  // 🎵 REAL-TIME STREAMING: Send audio chunks immediately for v1.03 compatibility
  // mod_audio_stream v1.03 supports raw binary streaming without JSON wrapper
  dgAgent.on(AgentEvents.Audio, (audioBuffer: Buffer) => {
    // Skip if connection is not ready
    if (fsSocket.readyState !== fsSocket.OPEN || !connectionState.agentReady) {
      return;
    }

    try {
      // 🚀 v1.0.3: Send raw binary stream (requires STREAM_SAMPLE_RATE=8000)
      // For versions that support raw binary bi-directional streaming
      fsSocket.send(audioBuffer);
      
      // Update stats (non-blocking)
      connectionState.audioStats.streamedChunks += 1;
      connectionState.audioStats.totalBytes += audioBuffer.length;
      
      // Log streaming progress
      const now = Date.now();
      if (now - connectionState.audioStats.lastLogTime > 2000) {
        console.log(`🎵 Streaming audio: ${connectionState.audioStats.streamedChunks} chunks (${connectionState.audioStats.totalBytes} bytes total)`);
        connectionState.audioStats.lastLogTime = now;
      }
      
    } catch (error) {
      console.error(`❌ Audio streaming error:`, error);
    }
  });


  dgAgent.on(AgentEvents.UserStartedSpeaking, () => {
    console.log(`🎤 User started speaking...`);
  });

  dgAgent.on(AgentEvents.AgentThinking, () => {
    console.log(`🔇 User stopped speaking, agent is thinking...`);
  });

  dgAgent.on(AgentEvents.ConversationText, (message: { role: string; content: string }) => {
    const timestamp = new Date().toISOString().split('T')[1].split('.')[0];
    const roleIcon = message.role === 'user' ? '👤' : '🤖';
    console.log(`\n[${timestamp}] ${roleIcon} ${message.role.toUpperCase()}: ${message.content}`);
  });

  dgAgent.on(AgentEvents.AgentStartedSpeaking, () => {
    console.log(`🤖 Agent started speaking - streaming audio in real-time...`);
  });

  dgAgent.on(AgentEvents.AgentAudioDone, () => {
    console.log(`✅ Agent finished speaking - real-time streaming complete`);
  });

  dgAgent.on(AgentEvents.Error, (error: Error) => {
    console.error(`Agent error for ${connectionId}:`, error);
  });

  dgAgent.on(AgentEvents.Close, () => {
    console.log(`Agent connection closed for ${connectionId}`);
    if (fsSocket.readyState === fsSocket.OPEN) {
      fsSocket.close();
    }
  });

  // Handle incoming messages from FreeSWITCH (binary PCM or JSON)
  fsSocket.on('message', (data: Buffer | string) => {
    if (!connectionState.agentReady) {
      return;
    }

    // Check if this is a text message (JSON) or binary audio data
    if (typeof data === 'string' || (Buffer.isBuffer(data) && data.length > 0 && data[0] === 0x7B)) {
      // This looks like JSON (starts with '{' = 0x7B)
      try {
        const textData = typeof data === 'string' ? data : data.toString('utf8');
        const jsonData = JSON.parse(textData);
        console.log(`📨 Received JSON from FreeSWITCH:`, jsonData);
        
        // 🚀 v1.0.3: Handle mod_audio_stream events
        if (jsonData.status) {
          switch (jsonData.status) {
            case 'connected':
              console.log(`🔗 mod_audio_stream connected successfully`);
              break;
            case 'disconnected':
              console.log(`🔌 mod_audio_stream disconnected: ${jsonData.message?.reason || 'Unknown reason'}`);
              break;
            case 'error':
              console.error(`❌ mod_audio_stream error: ${jsonData.message?.error || 'Unknown error'}`);
              break;
          }
        }
        
        // Handle playback events from v1.0.3
        if (jsonData.type === 'streamAudio' && jsonData.data) {
          console.log(`🎵 Received audio playback request from mod_audio_stream`);
          // The audio will be automatically played by mod_audio_stream v1.0.3
          // No additional action needed - this is handled by the module
        }
        
        return;
      } catch (error) {
        console.warn(`⚠️  Failed to parse JSON message:`, error);
      }
    }

    // Handle binary PCM audio data
    const audioData = Buffer.isBuffer(data) ? data : Buffer.from(data);
    
    // Validate frame size (should be multiples of bytes_per_sample)
    if (audioData.length % AUDIO_CONFIG.bytes_per_sample !== 0) {
      console.warn(`⚠️  Invalid PCM frame size: ${audioData.length} bytes`);
      return;
    }

    try {
      // Convert Buffer to ArrayBuffer for Deepgram SDK compatibility
      const arrayBuffer = audioData.buffer.slice(audioData.byteOffset, audioData.byteOffset + audioData.byteLength);
      dgAgent.send(arrayBuffer);
      
      // Update audio statistics
      connectionState.audioStats.totalBytes += audioData.length;
      connectionState.audioStats.totalFrames += 1;
      
      // Log aggregated stats every 10 seconds instead of every frame
      const now = Date.now();
      if (now - connectionState.audioStats.lastLogTime > 10000) {
        const avgFrameSize = Math.round(connectionState.audioStats.totalBytes / connectionState.audioStats.totalFrames);
        const durationSeconds = ((now - connectionState.startTime) / 1000).toFixed(1);
        console.log(`🎤 Audio stats: ${connectionState.audioStats.totalFrames} frames, ${connectionState.audioStats.totalBytes} bytes total (avg ${avgFrameSize} bytes/frame) - ${durationSeconds}s session`);
        connectionState.audioStats.lastLogTime = now;
      }
    } catch (error) {
      console.error(`❌ Error forwarding audio to agent:`, error);
    }
  });

  fsSocket.on('close', (code: number, reason: string) => {
    const sessionDuration = ((Date.now() - connectionState.startTime) / 1000).toFixed(1);
    console.log(`🔌 Connection closed: ${code} ${reason} (${sessionDuration}s session)`);
    
    // Clean up agent connection
    if (dgAgent) {
      dgAgent.disconnect();
    }
    
    connections.delete(connectionId);
  });

  fsSocket.on('error', (error: Error) => {
    console.error(`❌ WebSocket error:`, error);
  });

  // Send ping periodically to keep connection alive
  const pingInterval = setInterval(() => {
    if (fsSocket.readyState === fsSocket.OPEN) {
      fsSocket.ping();
    } else {
      clearInterval(pingInterval);
    }
  }, 30000); // 30 seconds
});

// Health check endpoint
server.on('request', (req, res) => {
  if (req.url === '/health') {
    res.writeHead(200, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({
      status: 'ok',
      connections: connections.size,
      uptime: process.uptime()
    }));
  } else {
    res.writeHead(404);
    res.end('Not Found');
  }
});

const PORT = process.env.BRIDGE_PORT || 7001;

server.listen(PORT, () => {
  console.log(`FreeSWITCH bridge server listening on port ${PORT}`);
  console.log(`WebSocket endpoint: ws://localhost:${PORT}/fs`);
  console.log(`Health check: http://localhost:${PORT}/health`);
  console.log(`Audio format: ${AUDIO_CONFIG.encoding} ${AUDIO_CONFIG.sample_rate}Hz ${AUDIO_CONFIG.channels}ch`);
});

// Graceful shutdown
function shutdown() {
  console.log('\nShutting down bridge server...');
  
  // Close all connections
  connections.forEach((conn, id) => {
    console.log(`Closing connection ${id}`);
    if (conn.dgAgent) conn.dgAgent.disconnect();
    if (conn.fsSocket?.readyState === conn.fsSocket?.OPEN) conn.fsSocket.close();
  });
  
  connections.clear();
  
  server.close(() => {
    console.log('Bridge server shutdown complete');
    process.exit(0);
  });
}

process.on('SIGINT', shutdown);
process.on('SIGTERM', shutdown);

export default server;