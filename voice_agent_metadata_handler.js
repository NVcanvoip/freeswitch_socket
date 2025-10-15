/**
 * Voice Agent Metadata Handler Example
 * 
 * This example shows how to handle caller metadata in your voice agent WebSocket server
 */

const WebSocket = require('ws');
const { createClient, AgentEvents } = require('@deepgram/sdk');

// Example WebSocket server that handles caller metadata
class VoiceAgentWithMetadata {
  constructor(deepgramApiKey) {
    this.deepgram = createClient(deepgramApiKey);
    this.connections = new Map();
  }

  start(port = 8001) {
    const wss = new WebSocket.Server({ 
      port,
      path: '/voice/fs/v1'
    });

    wss.on('connection', (ws, req) => {
      this.handleConnection(ws, req);
    });

    console.log(`Voice Agent server listening on port ${port}`);
  }

  async handleConnection(ws, req) {
    const connectionId = this.generateConnectionId(req);
    console.log(`New connection: ${connectionId}`);
    
    // Parse caller metadata from URL parameters
    const metadata = this.parseCallerMetadata(req.url);
    console.log('Caller metadata:', metadata);

    // Enrich metadata with database lookup (if available)
    const enrichedMetadata = await this.enrichMetadata(metadata);
    
    // Create personalized Deepgram agent
    const agent = await this.createPersonalizedAgent(enrichedMetadata);
    
    // Store connection state
    this.connections.set(connectionId, {
      ws,
      agent,
      metadata: enrichedMetadata,
      startTime: Date.now()
    });

    // Set up WebSocket handlers
    this.setupWebSocketHandlers(connectionId);
  }

  parseCallerMetadata(url) {
    try {
      const urlObj = new URL(url, 'ws://localhost');
      const params = urlObj.searchParams;
      
      return {
        caller_id: params.get('caller_id') || 'unknown',
        caller_name: params.get('caller_name') || 'unknown',
        uuid: params.get('uuid') || '',
        destination: params.get('destination') || '',
        customer_id: params.get('customer_id') || '',
        domain_id: params.get('domain_id') || '',
        direction: params.get('direction') || 'inbound',
        network_addr: params.get('network_addr') || '',
        timestamp: params.get('timestamp') || Date.now()
      };
    } catch (error) {
      console.error('Error parsing metadata:', error);
      return { caller_id: 'unknown', caller_name: 'unknown' };
    }
  }

  async enrichMetadata(metadata) {
    // Example: Look up customer information from database
    try {
      // This would be your actual database call
      const customerInfo = await this.lookupCustomer(metadata.customer_id);
      const callerHistory = await this.getCallerHistory(metadata.caller_id);
      
      return {
        ...metadata,
        customer_name: customerInfo?.name || 'Unknown Customer',
        customer_tier: customerInfo?.tier || 'standard',
        account_balance: customerInfo?.balance || 0,
        previous_calls: callerHistory?.length || 0,
        last_call_date: callerHistory?.[0]?.date || null,
        preferred_language: customerInfo?.language || 'en',
        support_priority: this.calculateSupportPriority(customerInfo, callerHistory)
      };
    } catch (error) {
      console.error('Error enriching metadata:', error);
      return metadata;
    }
  }

  async createPersonalizedAgent(metadata) {
    const agent = this.deepgram.agent();
    
    // Build personalized prompt based on metadata
    const personalizedPrompt = this.buildPersonalizedPrompt(metadata);
    
    // Configure agent
    agent.on(AgentEvents.Open, () => {
      console.log(`Agent connected for ${metadata.caller_name} (${metadata.caller_id})`);
    });

    agent.on('Welcome', () => {
      agent.configure({
        audio: {
          input: { encoding: 'linear16', sample_rate: 16000 },
          output: { encoding: 'linear16', sample_rate: 16000, container: 'none' }
        },
        agent: {
          language: metadata.preferred_language || 'en',
          listen: {
            provider: { type: 'deepgram', model: 'nova-2' }
          },
          think: {
            provider: { type: 'open_ai', model: 'gpt-4o' },
            prompt: personalizedPrompt
          },
          speak: {
            provider: { type: 'deepgram', model: 'aura-2-thalia-en' }
          },
          greeting: this.buildPersonalizedGreeting(metadata)
        }
      });
    });

    return agent;
  }

  buildPersonalizedPrompt(metadata) {
    let prompt = `You are a phone assistant for CallerTechnologies. `;
    
    // Add caller context
    if (metadata.caller_name !== 'unknown') {
      prompt += `The caller is ${metadata.caller_name} `;
    }
    prompt += `calling from ${metadata.caller_id}. `;
    
    // Add customer context
    if (metadata.customer_name && metadata.customer_name !== 'Unknown Customer') {
      prompt += `They represent ${metadata.customer_name}. `;
    }
    
    // Add tier-based instructions
    switch (metadata.customer_tier) {
      case 'enterprise':
        prompt += `This is an enterprise customer - provide priority support and detailed technical assistance. `;
        break;
      case 'premium':
        prompt += `This is a premium customer - provide enhanced support with expedited service. `;
        break;
      default:
        prompt += `This is a standard customer - provide friendly, helpful support. `;
    }
    
    // Add call history context
    if (metadata.previous_calls > 0) {
      prompt += `This caller has contacted us ${metadata.previous_calls} times before. `;
      if (metadata.last_call_date) {
        prompt += `Their last call was on ${new Date(metadata.last_call_date).toLocaleDateString()}. `;
      }
      prompt += `Be familiar and reference their history when appropriate. `;
    } else {
      prompt += `This appears to be a new caller - provide a warm welcome and clear explanations. `;
    }
    
    // Add call direction context
    if (metadata.direction === 'outbound') {
      prompt += `This is an outbound call - be proactive and focus on the reason for calling. `;
    }
    
    prompt += `Keep responses short and natural, under 30 words. `;
    prompt += `Focus on CallerTechnologies' offerings: VoIP systems, caller insights, automated marketing, and sales coaching.`;
    
    return prompt;
  }

  buildPersonalizedGreeting(metadata) {
    if (metadata.caller_name !== 'unknown' && metadata.previous_calls > 0) {
      return `Hi ${metadata.caller_name}! Thanks for calling CallerTechnologies again. How can I help you today?`;
    } else if (metadata.caller_name !== 'unknown') {
      return `Hi ${metadata.caller_name}! Welcome to CallerTechnologies. How can I assist you?`;
    } else if (metadata.previous_calls > 0) {
      return `Welcome back to CallerTechnologies! I see you've called before. How can I help you today?`;
    } else {
      return `Hi! Welcome to CallerTechnologies. How can I help you today?`;
    }
  }

  calculateSupportPriority(customerInfo, callerHistory) {
    let priority = 'normal';
    
    if (customerInfo?.tier === 'enterprise') priority = 'high';
    else if (customerInfo?.tier === 'premium') priority = 'medium';
    
    // Escalate if many recent calls
    const recentCalls = callerHistory?.filter(call => 
      new Date(call.date) > new Date(Date.now() - 7 * 24 * 60 * 60 * 1000)
    ).length || 0;
    
    if (recentCalls > 3) priority = 'high';
    
    return priority;
  }

  setupWebSocketHandlers(connectionId) {
    const connection = this.connections.get(connectionId);
    if (!connection) return;
    
    const { ws, agent, metadata } = connection;

    // Handle incoming audio from FreeSWITCH
    ws.on('message', (data) => {
      try {
        // Check if it's JSON metadata or binary audio
        if (typeof data === 'string' || (Buffer.isBuffer(data) && data[0] === 0x7B)) {
          const textData = typeof data === 'string' ? data : data.toString('utf8');
          const jsonData = JSON.parse(textData);
          this.handleJsonMessage(connectionId, jsonData);
        } else {
          // Binary audio data
          if (agent && data.length > 0) {
            const arrayBuffer = data.buffer.slice(data.byteOffset, data.byteOffset + data.byteLength);
            agent.send(arrayBuffer);
          }
        }
      } catch (error) {
        console.error(`Error handling message for ${connectionId}:`, error);
      }
    });

    // Handle WebSocket close
    ws.on('close', () => {
      console.log(`Connection closed: ${connectionId}`);
      if (agent) agent.disconnect();
      this.connections.delete(connectionId);
      
      // Log call completion with metadata
      this.logCallCompletion(metadata, connection.startTime);
    });

    // Handle agent responses
    agent.on(AgentEvents.Audio, (audioBuffer) => {
      if (ws.readyState === WebSocket.OPEN) {
        // Send audio back to FreeSWITCH in the expected format
        const jsonMessage = {
          type: 'streamAudio',
          data: {
            audioDataType: 'raw',
            sampleRate: 16000,
            audioData: audioBuffer.toString('base64')
          }
        };
        ws.send(JSON.stringify(jsonMessage));
      }
    });

    // Log conversation for analytics
    agent.on(AgentEvents.ConversationText, (message) => {
      this.logConversation(metadata, message);
    });
  }

  handleJsonMessage(connectionId, jsonData) {
    console.log(`JSON message from ${connectionId}:`, jsonData);
    
    // Handle different message types
    switch (jsonData.type) {
      case 'metadata_update':
        this.updateConnectionMetadata(connectionId, jsonData.data);
        break;
      case 'call_transfer':
        this.handleCallTransfer(connectionId, jsonData.data);
        break;
      default:
        console.log(`Unknown message type: ${jsonData.type}`);
    }
  }

  logCallCompletion(metadata, startTime) {
    const duration = Math.round((Date.now() - startTime) / 1000);
    console.log(`Call completed:`, {
      caller_id: metadata.caller_id,
      customer_id: metadata.customer_id,
      duration: `${duration}s`,
      timestamp: new Date().toISOString()
    });
    
    // Here you would save to your analytics database
    // this.analytics.logCall(metadata, duration);
  }

  logConversation(metadata, message) {
    console.log(`Conversation [${metadata.caller_id}]: ${message.role}: ${message.content}`);
    
    // Here you would save conversation logs
    // this.analytics.logMessage(metadata, message);
  }

  generateConnectionId(req) {
    return `${req.socket.remoteAddress}:${req.socket.remotePort}:${Date.now()}`;
  }

  // Mock database methods (replace with your actual database calls)
  async lookupCustomer(customerId) {
    // Mock customer lookup
    const mockCustomers = {
      '123': { name: 'Acme Corp', tier: 'enterprise', balance: 5000, language: 'en' },
      '456': { name: 'Tech Startup', tier: 'premium', balance: 1500, language: 'en' },
      '789': { name: 'Small Business', tier: 'standard', balance: 500, language: 'en' }
    };
    
    return mockCustomers[customerId] || null;
  }

  async getCallerHistory(callerId) {
    // Mock call history lookup
    const mockHistory = {
      '15551234567': [
        { date: '2024-01-15', duration: 300, type: 'support' },
        { date: '2024-01-10', duration: 180, type: 'sales' }
      ]
    };
    
    return mockHistory[callerId] || [];
  }
}

// Usage example
if (require.main === module) {
  const DEEPGRAM_API_KEY = process.env.DEEPGRAM_API_KEY;
  
  if (!DEEPGRAM_API_KEY) {
    console.error('Please set DEEPGRAM_API_KEY environment variable');
    process.exit(1);
  }
  
  const voiceAgent = new VoiceAgentWithMetadata(DEEPGRAM_API_KEY);
  voiceAgent.start(8001);
}

module.exports = VoiceAgentWithMetadata;
