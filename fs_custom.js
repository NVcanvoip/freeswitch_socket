const { FreeSwitchServer, once } = require('esl');
const WebSocket = require('ws');
const dgram = require('dgram');
const fs = require('fs');
const fsPromises = fs.promises;
const path = require('path');
const EventEmitter = require('events');
const { setTimeout } = require('timers/promises');


const SAMPLE_RATE = 8000;
const FRAME_DURATION_MS = 20; // 20 ms at 8 kHz
const FRAME_SIZE = Math.round((SAMPLE_RATE * FRAME_DURATION_MS) / 1000);
const BYTES_PER_SAMPLE = 2; // 16-bit PCM
const FRAME_BYTES = FRAME_SIZE * BYTES_PER_SAMPLE; // 320 bytes per frame
const FRAME_INTERVAL_MS = 20; // target 50 packets per second


const baseDeepgramWsUrl = "ws://54.218.134.236:8001/voice/fs/v1?caller_id=18186971437&destination=18188673475&webhook_url=https%3A%2F%2Fcallerwho.com%2Fclient_api%2Fcall_status";

function getLogTimestamp() {
    const now = new Date();
    const iso = now.toISOString();
    const date = iso.slice(0, 10);
    const time = iso.slice(11, 19);
    const hundredths = String(Math.floor(now.getMilliseconds() / 10)).padStart(2, '0');
    return `${date} ${time}.${hundredths}`;
}

function buildDeepgramWsUrl(baseUrl, callerId, destinationNumber) {
    if (!callerId && !destinationNumber) {
        return baseUrl;
    }

    try {
        const url = new URL(baseUrl);

        if (callerId) {
            url.searchParams.set('caller_id', callerId);
        }

        if (destinationNumber) {
            url.searchParams.set('destination', destinationNumber);
        }

        return url.toString();
    } catch (error) {
        console.warn('[Deepgram] Failed to build URL with metadata:', error);
        return baseUrl;
    }
}


const server = new FreeSwitchServer()
const channels = {};

const RTP_PORT_MIN = 40000;
const RTP_PORT_MAX = 50000;
const allocatedPorts = new Set();

function allocateRtpPort() {
    for (let port = RTP_PORT_MIN; port <= RTP_PORT_MAX; port++) {
        if (!allocatedPorts.has(port)) {
            allocatedPorts.add(port);
            return port;
        }
    }
    throw new Error('No available RTP ports in the specified range');
}

function releaseRtpPort(port) {
    if (port !== undefined) {
        allocatedPorts.delete(port);
    }
}


class Channel {
    constructor({ deepgramUrl } = {}) {
        this.ssrc = Math.floor(Math.random() * 0xFFFFFFFF);
        this.seqNum = 0;
        this.timestamp = 0;
        this.sock = dgram.createSocket('udp4');
        this.socketReady = false;
        this.bufferQueue = new EventEmitter();
        this.bufferQueue.setMaxListeners(100);
        this.port = undefined;
        this.dport = undefined;
        this.outboundQueue = [];
        this.frameRemainder = Buffer.alloc(0);
        this.isProcessingQueue = false;
        this.maxQueueSize = 20000; // Large buffer queue size to hold significant audio backlog
        this.uuid = null;
        this.recordingStream = null;
        this.recordingFilePath = null;
        this.recordingBytesWritten = 0;
        this.deepgramUrl = deepgramUrl || baseDeepgramWsUrl;

        this.sock.on('listening', () => {
            this.socketReady = true;
            this.logWithTimestamp('[RTP] UDP socket is listening. Resuming outbound queue processing.');
            this.processOutboundQueue();
        });

        this.sock.on('error', (error) => {
            console.error('[RTP] Socket error:', error);
            this.socketReady = false;
            this.sock.close();
        });

        this.sock.on('close', () => {
            this.socketReady = false;
            this.outboundQueue = [];
            this.frameRemainder = Buffer.alloc(0);
            this.isProcessingQueue = false;
            releaseRtpPort(this.dport);
            releaseRtpPort(this.port);
            this.dport = undefined;
            this.port = undefined;
            this.sock = null;
        });
    }


    logWithTimestamp(message) {
        console.log(`[${getLogTimestamp()}] ${message}`);
    }


    handleWebSocketPayload(payloadBuffer) {
        if (!payloadBuffer || payloadBuffer.length === 0) {
            return;
        }

        this.logWithTimestamp(`[WebSocket] Processing audio payload (${payloadBuffer.length} bytes).`);
        this.recordPayload(payloadBuffer);
        this.enqueueFrames(payloadBuffer);
    }


    enqueueFrames(buffer) {
        const normalizedBuffer = Buffer.isBuffer(buffer) ? buffer : Buffer.from(buffer);

        let workingBuffer = normalizedBuffer;
        if (this.frameRemainder.length > 0) {
            workingBuffer = Buffer.concat([this.frameRemainder, normalizedBuffer]);
            this.frameRemainder = Buffer.alloc(0);
        }

        while (workingBuffer.length >= FRAME_BYTES) {
            if (this.outboundQueue.length >= this.maxQueueSize) {
                this.logWithTimestamp(`[Queue] Maximum queue size (${this.maxQueueSize}) reached. Dropping frame.`);
                break;
            }

            const frame = workingBuffer.subarray(0, FRAME_BYTES);
            workingBuffer = workingBuffer.subarray(FRAME_BYTES);
            this.outboundQueue.push(frame);
            this.logWithTimestamp(`[Queue] Enqueued frame (${frame.length} bytes). Queue size: ${this.outboundQueue.length}.`);
        }

        if (workingBuffer.length > 0) {
            this.frameRemainder = workingBuffer;
            this.logWithTimestamp(`[Queue] Stored remainder (${this.frameRemainder.length} bytes) awaiting more data.`);
        }

        this.processOutboundQueue();
    }


    async processOutboundQueue() {
        if (this.isProcessingQueue) {
            return;
        }

        this.isProcessingQueue = true;

        try {
            while (this.outboundQueue.length > 0) {
                if (!this.sock || !this.socketReady || this.port === undefined || !this.rtpAdress) {
                    this.logWithTimestamp('[Queue] Socket not ready. Pausing outbound processing.');
                    break;
                }

                const frame = this.outboundQueue.shift();
                this.logWithTimestamp(`[Queue] Dequeued frame (${frame.length} bytes) for sending. Queue size after dequeue: ${this.outboundQueue.length}.`);
                try {
                    await this.sendPcmFrame(frame);
                } catch (error) {
                    console.error(`[${getLogTimestamp()}] [RTP] Error while sending PCM frame:`, error);
                    this.outboundQueue.unshift(frame);
                    break;
                }

                if (this.outboundQueue.length > 0) {
                    await setTimeout(FRAME_INTERVAL_MS);
                }
            }
        } finally {
            this.isProcessingQueue = false;

            if (this.outboundQueue.length > 0 && this.sock && this.socketReady && this.port !== undefined && this.rtpAdress) {
                this.processOutboundQueue();
            }
        }
    }


    sendPcmFrame(frame) {
        return new Promise((resolve, reject) => {
            if (!this.sock || !this.socketReady || this.port === undefined || !this.rtpAdress) {
                return reject(new Error('Socket unavailable for sending PCM frame'));
            }

            this.sock.send(frame, this.port, this.rtpAdress, (error) => {
                if (error) {
                    console.error(`[${getLogTimestamp()}] [RTP] Failed to send PCM frame:`, error);
                    reject(error);
                    return;
                }

                this.logWithTimestamp(`[RTP] Sent PCM frame (${frame.length} bytes). Queue size after send: ${this.outboundQueue.length}.`);
                resolve();
            });
        });
    }


    receiveAudio() {
        this.sock.on('message', (message, client) => {
            if (message.length < 12) {
                console.log('Error: received packet is smaller than 12 bytes!');
                return;
            }
            this.bufferQueue.emit('data', message);
        });
    }


    setDeepgramUrl(deepgramUrl) {
        if (deepgramUrl) {
            this.deepgramUrl = deepgramUrl;
        }
    }


    sendAudioSTT() {
        // Initialize Deepgram WebSocket
        console.log('Attempting to connect to Deepgram');

        const targetUrl = this.deepgramUrl || baseDeepgramWsUrl;
        this.deepgramWs = new WebSocket(targetUrl);

    this.deepgramWs.on("open", () => {
            console.log('[Deepgram] Connected');
//	    deepgramWs.send(JSON.stringify({ event: 'connected' }));
        this.bufferQueue.on('data', (audioData) => {
        if (!audioData) return;
//        	const audioData2 = audioData.toString('base64');
//            	console.log('Send AUDIO');
        this.deepgramWs.send(audioData);

        });
    });

        this.deepgramWs.on("message", async (message) => {
            const payloadBuffer = Buffer.isBuffer(message) ? message : Buffer.from(message);
            this.logWithTimestamp(`[WebSocket] Message received (${payloadBuffer.length} bytes).`);
            const jsonPayload = this.parseDeepgramJsonPayload(payloadBuffer);

            if (jsonPayload) {
                const { response } = jsonPayload;
                console.log('[Deepgram] Response received: ', response.is_final, response.speech_final);
                if (response.is_final === true || response.speech_final === true) {
                    const transcript = response.channel.alternatives?.[0]?.transcript;
                    if (transcript) {
                        console.log('Transcript: ', transcript);
                    }
                }
                console.log('[Deepgram] Response received: ', JSON.stringify(response, null, 2));
                return;
            }

            this.handleWebSocketPayload(payloadBuffer);
        });
    }


    parseDeepgramJsonPayload(buffer) {
        if (!buffer || buffer.length === 0) {
            return null;
        }

        let index = 0;
        const { length } = buffer;
        while (index < length) {
            const byte = buffer[index];
            if (byte === 0x20 || byte === 0x09 || byte === 0x0A || byte === 0x0D) {
                index += 1;
                continue;
            }
            break;
        }

        if (index >= length) {
            return null;
        }

        const firstByte = buffer[index];
        if (firstByte !== 0x7B && firstByte !== 0x5B) {
            return null;
        }

        try {
            const text = buffer.toString('utf8');
            const response = JSON.parse(text);
            return { response };
        } catch (error) {
            return null;
        }
    }


    sendAudio(address, port) {
        this.bufferQueue.on('data', (audioData) => {
            if (!audioData) {
                return;
            }

            const rtpPacket = audioData;
            setTimeout(1000).then(() => {
                if (!this.sock || !this.socketReady) {
                    console.warn('[RTP] Attempted to send a packet on an inactive socket. Packet dropped.');
                    return;
                }

                try {
                    this.sock.send(rtpPacket, port, address, (error) => {
                        if (error) {
                            console.error('[RTP] Error sending packet:', error);
                        }
                    });
                } catch (error) {
                    if (error && error.code === 'ERR_SOCKET_DGRAM_NOT_RUNNING') {
                        console.warn('[RTP] Attempted to send through a stopped socket:', error.message || error);
                    } else {
                        console.error('[RTP] Unexpected error sending packet:', error);
                    }
                }
            });
        });
    }

    async init(call, uuid) {
        try {
            this.port = allocateRtpPort();
            this.dport = allocateRtpPort();
        } catch (error) {
            releaseRtpPort(this.port);
            this.port = undefined;
            throw error;
        }
        this.uuid = uuid;
        this.rtpAdress='127.0.0.1';
        this.sock.bind(this.dport);
        this.receiveAudio();

    try {
      const result = await call.unicast_uuid(uuid, {
            'local-ip': this.rtpAdress,
            'local-port': this.port,
            'remote-ip': this.rtpAdress,
            'remote-port': this.dport,
            transport: 'udp',
          });
          console.log('Unicast result:', result);
    } catch (error) {
          console.error('Unicast error:', error);
    }

//        await setTimeout(3000); // for echo test
//        this.sendAudio(this.rtpAdress, this.port); // for echo test
        this.sendAudioSTT(); // to DEEPGRAM!!!!
    }

    cleanup() {
        this.bufferQueue.removeAllListeners();

        if (this.deepgramWs) {
            try {
                this.deepgramWs.close();
            } catch (error) {
                console.error('[Deepgram] Error closing WebSocket:', error);
            }
            this.deepgramWs = null;
        }

        this.deepgramUrl = baseDeepgramWsUrl;

        if (this.sock) {
            this.sock.removeAllListeners('message');
            try {
                this.sock.close();
            } catch (error) {
                console.error('[RTP] Error closing socket:', error);
            }
            this.sock = null;
        }

        this.outboundQueue = [];
        this.frameRemainder = Buffer.alloc(0);
        this.isProcessingQueue = false;

        this.finishRecording();

        releaseRtpPort(this.dport);
        releaseRtpPort(this.port);
        this.dport = undefined;
        this.port = undefined;
        this.socketReady = false;
    }


    ensureRecordingStream() {
        if (this.recordingStream) {
            return;
        }

        try {
            const recordingsDir = path.join(__dirname, 'recordings');
            if (!fs.existsSync(recordingsDir)) {
                fs.mkdirSync(recordingsDir, { recursive: true });
            }

            const fileName = `${this.uuid || 'channel'}_${Date.now()}.wav`;
            this.recordingFilePath = path.join(recordingsDir, fileName);
            const header = this.createWavHeader(0);
            this.recordingStream = fs.createWriteStream(this.recordingFilePath);
            this.recordingStream.write(header);
        } catch (error) {
            console.error('[Recording] Failed to initialize recording stream:', error);
            this.recordingStream = null;
            this.recordingFilePath = null;
            this.recordingBytesWritten = 0;
        }
    }


    recordPayload(buffer) {
        if (!buffer || buffer.length === 0) {
            return;
        }

        this.ensureRecordingStream();
        if (!this.recordingStream) {
            return;
        }

        this.recordingBytesWritten += buffer.length;
        if (!this.recordingStream.write(buffer)) {
            this.recordingStream.once('drain', () => {});
        }
    }


    finishRecording() {
        if (!this.recordingStream || !this.recordingFilePath) {
            return;
        }

        const dataLength = this.recordingBytesWritten;
        const filePath = this.recordingFilePath;
        this.recordingStream.end(async () => {
            try {
                const header = this.createWavHeader(dataLength);
                const fileHandle = await fsPromises.open(filePath, 'r+');
                await fileHandle.write(header, 0, header.length, 0);
                await fileHandle.close();
            } catch (error) {
                console.error('[Recording] Failed to finalize WAV header:', error);
            }
        });

        this.recordingStream = null;
        this.recordingFilePath = null;
        this.recordingBytesWritten = 0;
    }


    createWavHeader(dataSize) {
        const sampleRate = 8000;
        const bitsPerSample = 16;
        const channels = 1;
        const byteRate = sampleRate * channels * bitsPerSample / 8;
        const blockAlign = channels * bitsPerSample / 8;

        const buffer = Buffer.alloc(44);
        buffer.write('RIFF', 0);
        buffer.writeUInt32LE(36 + dataSize, 4);
        buffer.write('WAVE', 8);
        buffer.write('fmt ', 12);
        buffer.writeUInt32LE(16, 16);
        buffer.writeUInt16LE(1, 20);
        buffer.writeUInt16LE(channels, 22);
        buffer.writeUInt32LE(sampleRate, 24);
        buffer.writeUInt32LE(byteRate, 28);
        buffer.writeUInt16LE(blockAlign, 32);
        buffer.writeUInt16LE(bitsPerSample, 34);
        buffer.write('data', 36);
        buffer.writeUInt32LE(dataSize, 40);
        return buffer;
    }
}


server.on('connection', async (call ,{headers, body, data, uuid}) => {
  console.log('Incoming call for UUID', uuid);
  call.noevents();
  call.event_json('CHANNEL_ANSWER');
  call.event_json('CHANNEL_HANGUP_COMPLETE');
  call.event_json('CHANNEL_DESTROY');
  call.execute('answer');

  const cleanupChannel = (reason) => {
    const fsChannel = channels[uuid];
    if (!fsChannel) {
      return;
    }

    console.log('Call cleanup triggered by', reason, uuid);
    delete channels[uuid];
    fsChannel.cleanup();
  };

  call.on('CHANNEL_ANSWER', async function({headers,body}) {
    console.log('Call was answered');

    let callerIdNumber;
    let destinationNumber;

    if (body) {
      let eventBody;

      if (typeof body === 'string') {
        try {
          eventBody = JSON.parse(body);
        } catch (error) {
          console.warn('[CHANNEL_ANSWER] Failed to parse body string as JSON:', error);
        }
      } else if (Buffer.isBuffer(body)) {
        try {
          eventBody = JSON.parse(body.toString('utf8'));
        } catch (error) {
          console.warn('[CHANNEL_ANSWER] Failed to parse body buffer as JSON:', error);
        }
      } else if (typeof body === 'object') {
        eventBody = body;
      }

      if (eventBody && typeof eventBody === 'object') {
        const callerFromBody = eventBody['Caller-Caller-ID-Number'];
        const destinationFromBody = eventBody['Caller-Destination-Number'];

        if (callerFromBody !== undefined && callerFromBody !== null) {
          const normalized = String(callerFromBody).trim();
          if (normalized.length > 0) {
            callerIdNumber = normalized;
          }
        }

        if (destinationFromBody !== undefined && destinationFromBody !== null) {
          const normalized = String(destinationFromBody).trim();
          if (normalized.length > 0) {
            destinationNumber = normalized;
          }
        }
      }
    }

    const deepgramUrl = buildDeepgramWsUrl(baseDeepgramWsUrl, callerIdNumber, destinationNumber);

    console.log('Deepgram metadata for call %s -> caller: %s, destination: %s', uuid, callerIdNumber || 'unknown', destinationNumber || 'unknown');

    const fsChannel = new Channel({ deepgramUrl });
    await fsChannel.init(call, uuid);
    channels[uuid] = fsChannel;
  });

  call.on('CHANNEL_HANGUP_COMPLETE', function() {
    cleanupChannel('CHANNEL_HANGUP_COMPLETE');
  });

  call.on('CHANNEL_DESTROY', function() {
    cleanupChannel('CHANNEL_DESTROY');
  });

  call.once('freeswitch_disconnect', function() {
    cleanupChannel('freeswitch_disconnect');
  });

})

server.listen({ port: 8085 })
