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
const SEND_LEAD_MS = 5; // send each packet slightly before the 20 ms window ends


const baseDeepgramWsUrl = "ws://127.0.0.1:8765/voice/fs/v1";

function getLogTimestamp() {
    const now = new Date();
    const iso = now.toISOString();
    const date = iso.slice(0, 10);
    const time = iso.slice(11, 19);
    const hundredths = String(Math.floor(now.getMilliseconds() / 10)).padStart(2, '0');
    return `${date} ${time}.${hundredths}`;
}

function buildDeepgramWsUrl(baseUrl, metadata = {}) {
    if (!metadata || typeof metadata !== 'object' || Object.keys(metadata).length === 0) {
        return baseUrl;
    }

    try {
        const url = new URL(baseUrl);
        Object.entries(metadata).forEach(([key, value]) => {
            if (value === undefined || value === null) {
                return;
            }

            const normalizedValue = String(value).trim();
            if (normalizedValue.length === 0) {
                return;
            }

            url.searchParams.set(key, normalizedValue);
        });

        return url.toString();
    } catch (error) {
        console.warn('[Deepgram] Failed to build URL with metadata:', error);
        return baseUrl;
    }
}


function parseEventBody(body) {
    if (!body) {
        return null;
    }

    if (typeof body === 'string') {
        try {
            return JSON.parse(body);
        } catch (error) {
            console.warn('[FreeSWITCH] Failed to parse event body string as JSON:', error);
            return null;
        }
    }

    if (Buffer.isBuffer(body)) {
        try {
            return JSON.parse(body.toString('utf8'));
        } catch (error) {
            console.warn('[FreeSWITCH] Failed to parse event body buffer as JSON:', error);
            return null;
        }
    }

    if (typeof body === 'object') {
        return body;
    }

    return null;
}


function extractCallMetadataFromEvent(eventBody) {
    if (!eventBody || typeof eventBody !== 'object') {
        return {};
    }

    const metadata = {};

    const assignIfPresent = (targetKey, ...candidateKeys) => {
        for (const key of candidateKeys) {
            if (Object.prototype.hasOwnProperty.call(eventBody, key)) {
                const raw = eventBody[key];
                if (raw !== undefined && raw !== null) {
                    const normalized = String(raw).trim();
                    if (normalized.length > 0) {
                        metadata[targetKey] = normalized;
                        return;
                    }
                }
            }
        }
    };

    assignIfPresent('caller_id', 'Caller-Caller-ID-Number', 'Caller-ANI');
    assignIfPresent('caller_name', 'Caller-Caller-ID-Name', 'Caller-Orig-Caller-ID-Name');
    assignIfPresent('uuid', 'Channel-Call-UUID', 'Unique-ID', 'variable_uuid');
    assignIfPresent('destination', 'Caller-Destination-Number', 'variable_sip_to_user');
    assignIfPresent('customer_id', 'variable_vtpbx_customer_id');
    assignIfPresent('domain_id', 'variable_vtpbx_domain_id');
    assignIfPresent('direction', 'Call-Direction', 'Caller-Direction', 'variable_direction');
    assignIfPresent('network_addr', 'Caller-Network-Addr', 'variable_sip_network_ip');
    assignIfPresent('webhook_url', 'variable_webhook_url');
    assignIfPresent('webhook_token', 'variable_webhook_token');

    const eventTimestamp = eventBody['Event-Date-Timestamp'];
    if (eventTimestamp !== undefined && eventTimestamp !== null) {
        const numericTimestamp = Number(eventTimestamp);
        if (!Number.isNaN(numericTimestamp) && Number.isFinite(numericTimestamp)) {
            metadata.timestamp = Math.floor(numericTimestamp / 1000000);
        }
    }

    return metadata;
}


function normalizeChannelState(eventBody) {
    if (!eventBody || typeof eventBody !== 'object') {
        return null;
    }

    const candidates = [
//        eventBody['Channel-State'],
        eventBody['Channel-Call-State'],
        eventBody['Channel-State-Desc'],
        eventBody['variable_callstate'],
        eventBody['Callstate'],
    ];

    for (const candidate of candidates) {
        if (candidate === undefined || candidate === null) {
            continue;
        }

        const normalized = String(candidate).trim().toUpperCase();
        if (normalized.length > 0) {
            return normalized;
        }
    }

    return null;
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
        this.nextFrameBoundaryMs = null;
        this.deepgramWs = null;
        this.readyPromise = null;
        this.readyResolve = null;
        this.readyReject = null;
        this.hasAcknowledgedReady = false;
        this.bufferForwarder = null;

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

                const sendCompletedAtMs = Number(process.hrtime.bigint() / 1000000n);

                if (this.nextFrameBoundaryMs === null) {
                    this.nextFrameBoundaryMs = sendCompletedAtMs + FRAME_INTERVAL_MS;
                } else {
                    const theoreticalNextBoundary = this.nextFrameBoundaryMs + FRAME_INTERVAL_MS;
                    const minimumNextBoundary = sendCompletedAtMs + FRAME_INTERVAL_MS;
                    this.nextFrameBoundaryMs = Math.max(theoreticalNextBoundary, minimumNextBoundary);
                }

                if (this.outboundQueue.length > 0) {
                    const currentTimeMs = Number(process.hrtime.bigint() / 1000000n);
                    const targetSendTimeMs = this.nextFrameBoundaryMs - SEND_LEAD_MS;
                    const waitTimeMs = Math.max(0, targetSendTimeMs - currentTimeMs);

                    if (waitTimeMs > 0) {
                        await setTimeout(waitTimeMs);
                    }
                }
            }
        } finally {
            this.isProcessingQueue = false;

            if (this.outboundQueue.length > 0 && this.sock && this.socketReady && this.port !== undefined && this.rtpAdress) {
                this.processOutboundQueue();
            }
        }

        if (this.outboundQueue.length === 0) {
            this.nextFrameBoundaryMs = null;
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


    createReadyPromise() {
        if (!this.readyPromise) {
            this.readyPromise = new Promise((resolve, reject) => {
                this.readyResolve = resolve;
                this.readyReject = reject;
            });
        }
    }


    waitForReady() {
        if (this.hasAcknowledgedReady) {
            return Promise.resolve();
        }

        this.createReadyPromise();
        return this.readyPromise;
    }


    async connectDeepgramWebSocket() {
        if (this.deepgramWs) {
            return this.waitForReady();
        }

        console.log('Attempting to connect to Deepgram');

        const targetUrl = this.deepgramUrl || baseDeepgramWsUrl;
        this.createReadyPromise();
        this.deepgramWs = new WebSocket(targetUrl);

        this.deepgramWs.on('open', () => {
            console.log('[Deepgram] Connected');
        });

        this.deepgramWs.on('message', (message) => {
            this.handleDeepgramMessage(message);
        });

        this.deepgramWs.on('error', (error) => {
            console.error('[Deepgram] WebSocket error:', error);
            if (this.readyReject) {
                this.readyReject(error);
                this.readyReject = null;
                this.readyResolve = null;
            }
        });

        this.deepgramWs.on('close', () => {
            console.log('[Deepgram] WebSocket closed');
            if (!this.hasAcknowledgedReady && this.readyReject) {
                this.readyReject(new Error('Deepgram WebSocket closed before ready event'));
                this.readyReject = null;
                this.readyResolve = null;
            }
            this.deepgramWs = null;
        });

        return this.waitForReady();
    }


    startAudioStreaming() {
        if (this.bufferForwarder) {
            return;
        }

        this.bufferForwarder = (audioData) => {
            if (!audioData) {
                return;
            }

            if (!this.deepgramWs || this.deepgramWs.readyState !== WebSocket.OPEN) {
                this.logWithTimestamp('[WebSocket] Deepgram socket not ready for audio. Dropping frame.');
                return;
            }

            this.deepgramWs.send(audioData);
        };

        this.bufferQueue.on('data', this.bufferForwarder);
    }


    handleDeepgramMessage(message) {
        const payloadBuffer = Buffer.isBuffer(message) ? message : Buffer.from(message);
        this.logWithTimestamp(`[WebSocket] Message received (${payloadBuffer.length} bytes).`);
        const jsonPayload = this.parseDeepgramJsonPayload(payloadBuffer);

        if (jsonPayload) {
            const { response } = jsonPayload;
            if (response && typeof response === 'object') {
                if (response.type === 'ready_to_greet') {
                    this.handleReadyToGreet(response);
                    return;
                }

                if (response.is_final === true || response.speech_final === true) {
                    const transcript = response.channel?.alternatives?.[0]?.transcript;
                    if (transcript) {
                        console.log('Transcript: ', transcript);
                    }
                }

                console.log('[Deepgram] Response received: ', JSON.stringify(response, null, 2));
                return;
            }
        }

        this.handleWebSocketPayload(payloadBuffer);
    }


    handleReadyToGreet(payload) {
        if (!this.deepgramWs || this.hasAcknowledgedReady) {
            return;
        }

        if (this.deepgramWs.readyState !== WebSocket.OPEN) {
            const error = new Error('Deepgram WebSocket not open for ready acknowledgment');
            console.error('[Deepgram] Cannot send ready acknowledgment:', error);
            if (this.readyReject) {
                this.readyReject(error);
                this.readyReject = null;
                this.readyResolve = null;
            }
            return;
        }

        const commandTimestamp = payload.timestamp;
        const acknowledgmentPayload = {
            type: 'acknowledgment',
            command: 'ready_to_greet',
            command_timestamp: commandTimestamp,
            status: 'success',
            timestamp: Math.floor(Date.now() / 1000),
            message: 'custom message',
        };

        this.createReadyPromise();

        this.deepgramWs.send(JSON.stringify(acknowledgmentPayload), (error) => {
            if (error) {
                console.error('[Deepgram] Failed to send ready acknowledgment:', error);
                if (this.readyReject) {
                    this.readyReject(error);
                    this.readyReject = null;
                    this.readyResolve = null;
                }
                return;
            }

            console.log('[Deepgram] Sent ready acknowledgment');
            this.hasAcknowledgedReady = true;
            if (this.readyResolve) {
                this.readyResolve(payload);
                this.readyResolve = null;
                this.readyReject = null;
            }
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
        await this.connectDeepgramWebSocket();
        this.startAudioStreaming();
    }

    cleanup() {
        if (this.bufferForwarder) {
            this.bufferQueue.removeListener('data', this.bufferForwarder);
            this.bufferForwarder = null;
        }

        this.bufferQueue.removeAllListeners();

        if (this.deepgramWs) {
            try {
                this.deepgramWs.close();
            } catch (error) {
                console.error('[Deepgram] Error closing WebSocket:', error);
            }
            this.deepgramWs = null;
        }

        this.readyPromise = null;
        this.readyResolve = null;
        this.readyReject = null;
        this.hasAcknowledgedReady = false;

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
  call.event_json('CHANNEL_CALLSTATE');
  call.event_json('CHANNEL_PROGRESS');
  call.event_json('CHANNEL_ANSWER');
  call.event_json('CHANNEL_HANGUP_COMPLETE');
  call.event_json('CHANNEL_DESTROY');

  let fsChannel = null;
  let metadata = {};
  let handshakeStarted = false;
  let answerRequested = false;

  const cleanupChannel = (reason) => {
    const existingChannel = channels[uuid] || fsChannel;
    if (!existingChannel) {
      return;
    }

    console.log('Call cleanup triggered by', reason, uuid);
    delete channels[uuid];
    existingChannel.cleanup();
    if (existingChannel === fsChannel) {
      fsChannel = null;
      metadata = {};
      handshakeStarted = false;
      answerRequested = false;
    }
  };

  try {
    call.execute('ring_ready');
  } catch (error) {
    console.error('Failed to send ring_ready:', error);
  }

  call.on('CHANNEL_PROGRESS', async ({ body }) => {
    if (handshakeStarted) {
      return;
    }

    const eventBody = parseEventBody(body);
    if (!eventBody) {
      return;
    }

    const normalizedState = normalizeChannelState(eventBody);
    if (!normalizedState || !normalizedState.includes('RING')) {
      return;
    }

    handshakeStarted = true;
    metadata = { ...metadata, ...extractCallMetadataFromEvent(eventBody) };
    if (!metadata.timestamp) {
      metadata.timestamp = Math.floor(Date.now() / 1000);
    }

    const targetUrl = buildDeepgramWsUrl(baseDeepgramWsUrl, metadata);

    console.error('Generate Target Url:', targetUrl);
    if (!fsChannel) {
      fsChannel = new Channel({ deepgramUrl: targetUrl });
      channels[uuid] = fsChannel;
    } else {
      fsChannel.setDeepgramUrl(targetUrl);
    }

    try {
      await fsChannel.connectDeepgramWebSocket();
      if (!answerRequested) {
        answerRequested = true;
        try {
          call.execute('answer');
        } catch (error) {
          console.error('Failed to execute answer command:', error);
        }
      }
    } catch (error) {
      console.error('[Deepgram] Failed during ready_to_greet handshake:', error);
      cleanupChannel('deepgram_handshake_error');
    }
  });

  call.on('CHANNEL_ANSWER', async ({ body }) => {
    console.log('Call was answered');
    answerRequested = true;

    const eventBody = parseEventBody(body);
    metadata = { ...metadata, ...extractCallMetadataFromEvent(eventBody) };
    if (!metadata.timestamp) {
      metadata.timestamp = Math.floor(Date.now() / 1000);
    }

    if (!fsChannel) {
      const targetUrl = buildDeepgramWsUrl(baseDeepgramWsUrl, metadata);
      fsChannel = new Channel({ deepgramUrl: targetUrl });
      channels[uuid] = fsChannel;
    }

    try {
      await fsChannel.init(call, uuid);
    } catch (error) {
      console.error('Failed to initialize channel after answer:', error);
      cleanupChannel('channel_init_failed');
    }
  });

  call.on('CHANNEL_HANGUP_COMPLETE', () => {
    cleanupChannel('CHANNEL_HANGUP_COMPLETE');
  });

  call.on('CHANNEL_DESTROY', () => {
    cleanupChannel('CHANNEL_DESTROY');
  });

  call.once('freeswitch_disconnect', () => {
    cleanupChannel('freeswitch_disconnect');
  });
});

server.listen({ port: 8085 })
