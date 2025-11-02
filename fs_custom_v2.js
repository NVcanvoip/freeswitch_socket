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

const ENABLE_PACKET_RECORDING = false;
const ENABLE_RTP_LOGGING = false;
const ENABLE_DEBUG_LOGGING = true;

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
    assignIfPresent('domain_name', 'variable_domain_name', 'variable_domain');
    assignIfPresent('direction', 'Call-Direction', 'Caller-Direction', 'variable_direction');
    assignIfPresent('network_addr', 'Caller-Network-Addr', 'variable_sip_network_ip');
    assignIfPresent('sip_from_uri', 'variable_sip_from_uri', 'variable_sip_from_addr');
    assignIfPresent('webhook_url', 'variable_webhook_url');
    assignIfPresent('webhook_token', 'variable_webhook_token');
    assignIfPresent('call_timeout', 'variable_call_timeout');
    assignIfPresent('ws_transfer_call_timeout', 'variable_ws_transfer_call_timeout');
    assignIfPresent('ws_transfer_external_gateway_id', 'variable_ws_transfer_external_gateway_id');
    assignIfPresent('ws_transfer_external_gateway_prefix', 'variable_ws_transfer_external_gateway_prefix');
    assignIfPresent('ws_transfer_ignore_early_media', 'variable_ws_transfer_ignore_early_media');

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
    constructor({ deepgramUrl, metadata } = {}) {
        this.ssrc = Math.floor(Math.random() * 0xFFFFFFFF);
        this.seqNum = 0;
        this.timestamp = 0;
        this.sock = dgram.createSocket('udp4');
        this.socketReady = false;
        this.bufferQueue = new EventEmitter();
        this.bufferQueue.setMaxListeners(100);
        this.port = undefined;
        this.metadata = metadata && typeof metadata === 'object' ? { ...metadata } : {};
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
        this.call = null;
        this.recordingEnabled = ENABLE_PACKET_RECORDING;
        this.hasSentHangupEvent = false;
        this.hasSentAnsweredEvent = false;
        this.finalizePromise = null;

        this.sock.on('listening', () => {
            this.socketReady = true;
            this.logWithTimestamp('[RTP] UDP socket is listening. Resuming outbound queue processing.', { category: 'rtp' });
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


    setCall(call) {
        if (!call) {
            return;
        }

        this.call = call;
    }

    setMetadata(metadata) {
        if (!metadata || typeof metadata !== 'object') {
            return;
        }

        this.metadata = { ...this.metadata, ...metadata };
    }

    getMetadata() {
        return { ...this.metadata };
    }


    logWithTimestamp(message, { level = 'log', category = 'debug' } = {}) {
        if (category === 'rtp' && !ENABLE_RTP_LOGGING && level === 'log') {
            return;
        }

        if (category === 'debug' && !ENABLE_DEBUG_LOGGING && level === 'log') {
            return;
        }

        const formatted = `[${getLogTimestamp()}] ${message}`;

        if (level === 'error') {
            console.error(formatted);
            return;
        }

        if (level === 'warn') {
            console.warn(formatted);
            return;
        }

        console.log(formatted);
    }


    logJsonPayload(direction, payload) {
        if (!ENABLE_DEBUG_LOGGING) {
            return;
        }

        try {
            const serialized = JSON.stringify(payload);
            this.logWithTimestamp(`[WebSocket][${direction}] ${serialized}`);
        } catch (error) {
            this.logWithTimestamp(`[WebSocket][${direction}] Failed to serialize JSON payload: ${error.message}`, { level: 'warn' });
        }
    }


    handleWebSocketPayload(payloadBuffer) {
        if (!payloadBuffer || payloadBuffer.length === 0) {
            return;
        }

        this.logWithTimestamp(`[WebSocket] Processing audio payload (${payloadBuffer.length} bytes).`, { category: 'rtp' });
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
            this.logWithTimestamp(`[Queue] Enqueued frame (${frame.length} bytes). Queue size: ${this.outboundQueue.length}.`, { category: 'rtp' });
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
                this.logWithTimestamp(`[Queue] Dequeued frame (${frame.length} bytes) for sending. Queue size after dequeue: ${this.outboundQueue.length}.`, { category: 'rtp' });
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

                this.logWithTimestamp(`[RTP] Sent PCM frame (${frame.length} bytes). Queue size after send: ${this.outboundQueue.length}.`, { category: 'rtp' });
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

        this.logWithTimestamp('[WebSocket] Attempting to connect to Deepgram.');

        const targetUrl = this.deepgramUrl || baseDeepgramWsUrl;
        this.createReadyPromise();
        this.deepgramWs = new WebSocket(targetUrl);

        this.deepgramWs.on('open', () => {
            this.logWithTimestamp('[WebSocket] Connected to Deepgram.');
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
            this.logWithTimestamp('[WebSocket] Deepgram WebSocket closed.');
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
        this.logWithTimestamp(`[WebSocket] Message received (${payloadBuffer.length} bytes).`, { category: 'rtp' });
        const jsonPayload = this.parseDeepgramJsonPayload(payloadBuffer);

        if (jsonPayload) {
            this.logJsonPayload('in', jsonPayload);

            if (jsonPayload.type === 'ready_to_greet') {
                this.handleReadyToGreet(jsonPayload);
                return;
            }

            if (jsonPayload.type === 'clear') {
                this.handleClearCommand(jsonPayload);
                return;
            }

            if (jsonPayload.type === 'end_call') {
                this.handleEndCallCommand(jsonPayload);
                return;
            }

            if (jsonPayload.type === 'transfer') {
                this.handleTransferCommand(jsonPayload);
                return;
            }

            const { response } = jsonPayload;
            if (response && typeof response === 'object') {
                if (response.type === 'ready_to_greet') {
                    this.handleReadyToGreet(response);
                    return;
                }

                if (response.is_final === true || response.speech_final === true) {
                    const transcript = response.channel?.alternatives?.[0]?.transcript;
                    if (transcript) {
                        this.logWithTimestamp(`Transcript: ${transcript}`);
                    }
                }

                let serializedResponse = '';
                try {
                    serializedResponse = JSON.stringify(response);
                } catch (error) {
                    serializedResponse = '[unserializable response]';
                }
                this.logWithTimestamp(`[Deepgram] Response received: ${serializedResponse}`);
                return;
            }

            return;
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

        this.sendJsonMessage(acknowledgmentPayload)
            .then(() => {
                this.logWithTimestamp('[Deepgram] Sent ready acknowledgment.');
                this.hasAcknowledgedReady = true;
                if (this.readyResolve) {
                    this.readyResolve(payload);
                    this.readyResolve = null;
                    this.readyReject = null;
                }
            })
            .catch((error) => {
                console.error('[Deepgram] Failed to send ready acknowledgment:', error);
                if (this.readyReject) {
                    this.readyReject(error);
                    this.readyReject = null;
                    this.readyResolve = null;
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
            return JSON.parse(text);
        } catch (error) {
            return null;
        }
    }


    async sendJsonMessage(payload) {
        if (!this.deepgramWs || this.deepgramWs.readyState !== WebSocket.OPEN) {
            const error = new Error('Deepgram WebSocket not open');
            this.logWithTimestamp(`[WebSocket] Unable to send JSON payload: ${error.message}`, { level: 'warn' });
            throw error;
        }

        let serialized;
        try {
            serialized = JSON.stringify(payload);
        } catch (error) {
            this.logWithTimestamp(`[WebSocket] Failed to serialize JSON payload: ${error.message}`, { level: 'warn' });
            throw error;
        }

        this.logJsonPayload('out', payload);

        await new Promise((resolve, reject) => {
            this.deepgramWs.send(serialized, (error) => {
                if (error) {
                    this.logWithTimestamp(`[WebSocket] Failed to send JSON payload: ${error.message}`, { level: 'warn' });
                    reject(error);
                    return;
                }

                resolve();
            });
        });
    }


    async sendAcknowledgment({ command, commandTimestamp, status = 'success', message }) {
        const payload = {
            type: 'acknowledgment',
            command,
            command_timestamp: commandTimestamp ?? Math.floor(Date.now() / 1000),
            status,
            timestamp: Math.floor(Date.now() / 1000),
        };

        if (message !== undefined) {
            payload.message = message;
        }

        await this.sendJsonMessage(payload);
    }


    clearOutboundQueue() {
        const clearedFrames = this.outboundQueue.length;
        this.outboundQueue = [];
        this.frameRemainder = Buffer.alloc(0);
        this.nextFrameBoundaryMs = null;
        this.isProcessingQueue = false;

        if (clearedFrames > 0) {
            this.logWithTimestamp(`[Queue] Cleared outbound queue (${clearedFrames} frames removed).`);
        } else {
            this.logWithTimestamp('[Queue] Outbound queue already empty.');
        }
    }


    async handleClearCommand(payload) {
        const receivedAt = Math.floor(Date.now() / 1000);
        this.clearOutboundQueue();

        try {
            await this.sendAcknowledgment({
                command: 'clear',
                commandTimestamp: payload?.timestamp ?? receivedAt,
                message: 'custom message',
            });
        } catch (error) {
            this.logWithTimestamp(`[WebSocket] Failed to send clear acknowledgment: ${error.message}`, { level: 'warn' });
        }
    }

    async handleTransferCommand(payload) {
        const now = Math.floor(Date.now() / 1000);
        const commandTimestamp = payload?.timestamp ?? now;
        const destinationRaw = payload?.destination;
        const destination = typeof destinationRaw === 'string' ? destinationRaw.trim() : '';

        const acknowledge = async (status, message) => {
            try {
                await this.sendAcknowledgment({
                    command: 'transfer',
                    commandTimestamp,
                    status,
                    message,
                });
            } catch (ackError) {
                const ackMessage = ackError?.message || String(ackError);
                this.logWithTimestamp(`[Transfer] Failed to send transfer acknowledgment: ${ackMessage}`, { level: 'warn' });
            }
        };

        if (!destination) {
            await acknowledge('error', 'missing destination');
            return;
        }

        if (!this.call) {
            await acknowledge('error', 'call not available');
            return;
        }

        const metadata = this.getMetadata();
        const parsePositiveInteger = (value, fallback) => {
            if (value === undefined || value === null) {
                return fallback;
            }

            const numeric = Number.parseInt(String(value), 10);
            if (Number.isNaN(numeric) || !Number.isFinite(numeric) || numeric <= 0) {
                return fallback;
            }

            return numeric;
        };

        const timeoutCandidate = metadata.ws_transfer_call_timeout ?? metadata.call_timeout;
        const legTimeout = parsePositiveInteger(timeoutCandidate, 30);
        const callerId = metadata.caller_id ?? '';
        const domainName = metadata.domain_name ?? '';
        let sipFromUri = metadata.sip_from_uri;
        if (!sipFromUri && callerId && domainName) {
            sipFromUri = `sip:${callerId}@${domainName}`;
        }

        const ignoreEarlyMedia = (metadata.ws_transfer_ignore_early_media ?? 'true').toString();
        const externalGatewayId = metadata.ws_transfer_external_gateway_id ?? '';
        const externalGatewayPrefix = metadata.ws_transfer_external_gateway_prefix ?? '';

        if (!externalGatewayId) {
            await acknowledge('error', 'missing external gateway id');
            return;
        }

        const dialStringOptions = [`leg_timeout=${legTimeout}`];
        if (sipFromUri) {
            dialStringOptions.push(`sip_from_uri=${sipFromUri}`);
        }

        if (callerId) {
            dialStringOptions.push(`origination_caller_id_number=${callerId}`);
        }

        if (ignoreEarlyMedia) {
            dialStringOptions.push(`ignore_early_media=${ignoreEarlyMedia}`);
        }

        const dialString = `[${dialStringOptions.join(',')}]sofia/gateway/gw${externalGatewayId}/${externalGatewayPrefix}${destination}`;
        this.logWithTimestamp(`[Transfer] Initiating bridge using dial string: ${dialString}`);

        try {
            await this.call.execute('bridge', dialString);
        } catch (error) {
            this.logWithTimestamp(`[Transfer] Failed to execute bridge: ${error.message}`, { level: 'error' });
            await acknowledge('error', error?.message || 'bridge failed');
            return;
        }

        await acknowledge('success', 'transfer initiated');
    }


    async handleEndCallCommand(payload) {
        const receivedAt = Math.floor(Date.now() / 1000);
        try {
            await this.sendAcknowledgment({
                command: 'end_call',
                commandTimestamp: payload?.timestamp ?? receivedAt,
            });
        } catch (error) {
            this.logWithTimestamp(`[WebSocket] Failed to send end_call acknowledgment: ${error.message}`, { level: 'warn' });
        }

        await this.finalize({ reason: 'NORMAL_CLEARING', hangupCall: true });
    }


    async sendCallEvent(event, { reason, timestamp } = {}) {
        const normalizedTimestamp = timestamp ?? Math.floor(Date.now() / 1000);
        let payloadReason = reason;

        if (event === 'answered') {
            if (this.hasSentAnsweredEvent) {
                return;
            }
            this.hasSentAnsweredEvent = true;
        }

        if (event === 'hangup') {
            if (this.hasSentHangupEvent) {
                return;
            }
            this.hasSentHangupEvent = true;
            if (!payloadReason) {
                payloadReason = 'NORMAL_CLEARING';
            }
        }

        const payload = {
            type: 'call_event',
            event,
            timestamp: normalizedTimestamp,
        };

        if (payloadReason) {
            payload.reason = payloadReason;
        }

        try {
            await this.sendJsonMessage(payload);
        } catch (error) {
            this.logWithTimestamp(`[WebSocket] Failed to send call event '${event}': ${error.message}`, { level: 'warn' });
        }
    }


    async notifyAnswered() {
        await this.sendCallEvent('answered');
    }


    async finalize({ reason = 'NORMAL_CLEARING', hangupCall = false } = {}) {
        if (this.finalizePromise) {
            return this.finalizePromise;
        }

        const execution = (async () => {
            if (hangupCall && this.call) {
                try {
                    await this.call.hangup(reason);
                } catch (error) {
                    console.error('[FreeSWITCH] Failed to hang up call:', error);
                }
            }

            const hangupReason = reason || 'NORMAL_CLEARING';
            await this.sendCallEvent('hangup', { reason: hangupReason });
            this.performCleanup();
        })();

        this.finalizePromise = execution.finally(() => {
            this.finalizePromise = null;
        });

        return this.finalizePromise;
    }


    performCleanup() {
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
        this.call = null;
        this.metadata = {};
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
        this.call = call;
        this.hasSentHangupEvent = false;
        this.hasSentAnsweredEvent = false;
        this.rtpAdress = '127.0.0.1';
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
            let serializedResult = '';
            try {
                serializedResult = JSON.stringify(result);
            } catch (serializationError) {
                serializedResult = '[unserializable result]';
            }
            this.logWithTimestamp(`[FreeSWITCH] Unicast result: ${serializedResult}`);
        } catch (error) {
            console.error('Unicast error:', error);
        }

        await this.connectDeepgramWebSocket();
        this.startAudioStreaming();
    }

    ensureRecordingStream() {
        if (!this.recordingEnabled) {
            return;
        }

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
        if (!this.recordingEnabled) {
            return;
        }

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
    existingChannel.finalize({ reason: 'NORMAL_CLEARING' }).catch((error) => {
      console.error('Failed to finalize channel gracefully:', error);
      existingChannel.performCleanup();
    });
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

    if (fsChannel) {
      fsChannel.setMetadata(metadata);
    }

    const targetUrl = buildDeepgramWsUrl(baseDeepgramWsUrl, metadata);

    console.error('Generate Target Url:', targetUrl);
    if (!fsChannel) {
      fsChannel = new Channel({ deepgramUrl: targetUrl, metadata });
      channels[uuid] = fsChannel;
      fsChannel.setCall(call);
      fsChannel.setMetadata(metadata);
    } else {
      fsChannel.setDeepgramUrl(targetUrl);
      fsChannel.setCall(call);
      fsChannel.setMetadata(metadata);
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
      fsChannel = new Channel({ deepgramUrl: targetUrl, metadata });
      channels[uuid] = fsChannel;
      fsChannel.setCall(call);
      fsChannel.setMetadata(metadata);
    } else {
      fsChannel.setCall(call);
      fsChannel.setMetadata(metadata);
    }

    try {
      await fsChannel.init(call, uuid);
      try {
        await fsChannel.notifyAnswered();
      } catch (error) {
        console.error('Failed to notify answered event:', error);
      }
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
