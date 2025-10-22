const { FreeSwitchServer } = require('esl');
const WebSocket = require('ws');
const dgram = require('dgram');
const EventEmitter = require('events');
const { setTimeout } = require('timers/promises');

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

const server = new FreeSwitchServer();
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
        this.sock = dgram.createSocket('udp4');
        this.socketReady = false;
        this.bufferQueue = new EventEmitter();
        this.bufferQueue.setMaxListeners(100);
        this.outboundQueue = [];
        this.maxQueueSize = 20000;
        this.isProcessingQueue = false;
        this.deepgramUrl = deepgramUrl || baseDeepgramWsUrl;
        this.uuid = null;
        this.port = undefined;
        this.dport = undefined;
        this.rtpAddress = undefined;
        this.mulawRemainder = Buffer.alloc(0);

        this.sock.on('listening', () => {
            this.socketReady = true;
            this.log('[RTP] UDP socket is listening.');
            this.processOutboundQueue();
        });

        this.sock.on('error', (error) => {
            this.log('[RTP] Socket error:', error);
            this.socketReady = false;
            try {
                this.sock.close();
            } catch (closeError) {
                this.log('[RTP] Socket close error:', closeError);
            }
        });

        this.sock.on('close', () => {
            this.log('[RTP] Socket closed.');
            this.socketReady = false;
            this.outboundQueue = [];
            this.isProcessingQueue = false;
            releaseRtpPort(this.dport);
            releaseRtpPort(this.port);
            this.dport = undefined;
            this.port = undefined;
            this.sock = null;
        });
    }

    log(message, ...args) {
        console.log(`[${getLogTimestamp()}] ${message}`, ...args);
    }

    enqueueOutbound(buffer) {
        if (!buffer || buffer.length === 0) {
            return;
        }

        const payload = Buffer.isBuffer(buffer) ? buffer : Buffer.from(buffer);

        if (this.outboundQueue.length >= this.maxQueueSize) {
            this.log(`[Queue] Maximum size reached (${this.maxQueueSize}). Dropping packet.`);
            return;
        }

        this.outboundQueue.push(payload);
        this.log(`[Queue] Enqueued payload (${payload.length} bytes). Queue size: ${this.outboundQueue.length}.`);
        this.processOutboundQueue();
    }

    async processOutboundQueue() {
        if (this.isProcessingQueue) {
            return;
        }

        this.isProcessingQueue = true;

        try {
            while (this.outboundQueue.length > 0) {
                if (!this.sock || !this.socketReady || this.port === undefined || !this.rtpAddress) {
                    this.log('[Queue] Socket not ready. Pausing outbound processing.');
                    break;
                }

                const payload = this.outboundQueue.shift();
                this.log(`[Queue] Dequeued payload (${payload.length} bytes). Remaining: ${this.outboundQueue.length}.`);

                try {
                    await this.sendPayload(payload);
                } catch (error) {
                    this.log('[RTP] Error while sending payload:', error);
                    this.outboundQueue.unshift(payload);
                    break;
                }

                if (this.outboundQueue.length > 0) {
                    await setTimeout(20);
                }
            }
        } finally {
            this.isProcessingQueue = false;

            if (this.outboundQueue.length > 0 && this.sock && this.socketReady && this.port !== undefined && this.rtpAddress) {
                this.processOutboundQueue();
            }
        }
    }

    sendPayload(payload) {
        return new Promise((resolve, reject) => {
            if (!this.sock || !this.socketReady || this.port === undefined || !this.rtpAddress) {
                return reject(new Error('Socket unavailable for sending payload'));
            }

            this.sock.send(payload, this.port, this.rtpAddress, (error) => {
                if (error) {
                    return reject(error);
                }

                this.log(`[RTP] Sent payload (${payload.length} bytes). Queue size: ${this.outboundQueue.length}.`);
                resolve();
            });
        });
    }

    receiveAudio() {
        this.sock.on('message', (message) => {
            if (!message || message.length === 0) {
                this.log('[RTP] Received empty packet.');
                return;
            }

            this.log(`[RTP] Received packet (${message.length} bytes). Forwarding to WebSocket.`);
            this.bufferQueue.emit('data', message);
        });
    }

    setDeepgramUrl(deepgramUrl) {
        if (deepgramUrl) {
            this.deepgramUrl = deepgramUrl;
        }
    }

    sendAudioSTT() {
        this.log('[Deepgram] Connecting to WebSocket.');
        const targetUrl = this.deepgramUrl || baseDeepgramWsUrl;
        this.deepgramWs = new WebSocket(targetUrl);

        this.deepgramWs.on('open', () => {
            this.log('[Deepgram] Connected.');
            this.bufferQueue.on('data', (audioData) => {
                if (!audioData || audioData.length === 0) {
                    return;
                }

                this.log(`[WebSocket] Sending audio chunk (${audioData.length} bytes).`);
                this.deepgramWs.send(audioData);
            });
        });

        this.deepgramWs.on('message', (message, isBinary) => {
            this.handleWebSocketMessage(message, isBinary);
        });

        this.deepgramWs.on('close', () => {
            this.log('[Deepgram] WebSocket closed.');
        });

        this.deepgramWs.on('error', (error) => {
            this.log('[Deepgram] WebSocket error:', error);
        });
    }

    handleWebSocketMessage(message, isBinary = false) {
        if (!isBinary && typeof message === 'string') {
            this.log(`[WebSocket] Message received (${message.length} chars).`);
            const jsonPayload = this.parseJsonPayload(message);
            if (jsonPayload) {
                this.log('[WebSocket] JSON payload received:', jsonPayload);
            }
            return;
        }

        const payloadBuffer = Buffer.isBuffer(message) ? message : Buffer.from(message);
        this.log(`[WebSocket] Message received (${payloadBuffer.length} bytes).`);

        const mulawFrames = this.splitMulawFrames(payloadBuffer);
        if (mulawFrames.length === 0) {
            if (this.mulawRemainder.length > 0) {
                this.log(`[WebSocket] Buffered ${this.mulawRemainder.length} bytes awaiting complete mu-law frame.`);
            } else {
                this.log('[WebSocket] No mu-law frames decoded from payload.');
            }
            return;
        }

        this.log('[WebSocket] Binary payload detected. Enqueuing mu-law frames for RTP send.');
        for (const frame of mulawFrames) {
            this.enqueueOutbound(frame);
        }
    }

    parseJsonPayload(payload) {
        if (!payload || payload.length === 0) {
            return null;
        }

        const text = typeof payload === 'string' ? payload : payload.toString('utf8');
        const trimmed = text.trim();

        if (!trimmed.startsWith('{') && !trimmed.startsWith('[')) {
            return null;
        }

        try {
            return JSON.parse(trimmed);
        } catch (error) {
            this.log('[WebSocket] Failed to parse JSON payload:', error.message);
            return null;
        }
    }

    splitMulawFrames(buffer) {
        if (!buffer || buffer.length === 0) {
            return [];
        }

        const FRAME_SIZE = 160;
        let workingBuffer = buffer;

        if (this.mulawRemainder && this.mulawRemainder.length > 0) {
            workingBuffer = Buffer.concat([this.mulawRemainder, buffer]);
            this.mulawRemainder = Buffer.alloc(0);
        }

        const frames = [];
        const completeLength = workingBuffer.length - (workingBuffer.length % FRAME_SIZE);

        for (let offset = 0; offset < completeLength; offset += FRAME_SIZE) {
            frames.push(workingBuffer.slice(offset, offset + FRAME_SIZE));
        }

        const remainderLength = workingBuffer.length - completeLength;
        if (remainderLength > 0) {
            this.mulawRemainder = workingBuffer.slice(completeLength);
        }

        return frames;
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
        this.rtpAddress = '127.0.0.1';
        this.sock.bind(this.dport);
        this.receiveAudio();

        try {
            const result = await call.unicast_uuid(uuid, {
                'local-ip': this.rtpAddress,
                'local-port': this.port,
                'remote-ip': this.rtpAddress,
                'remote-port': this.dport,
                transport: 'udp',
                flags: 'native',
            });
            this.log('[FreeSWITCH] Unicast result:', result);
        } catch (error) {
            this.log('[FreeSWITCH] Unicast error:', error);
        }

        this.sendAudioSTT();
    }

    cleanup() {
        this.log('[Channel] Cleanup started.');
        this.bufferQueue.removeAllListeners();

        if (this.deepgramWs) {
            try {
                this.deepgramWs.close();
            } catch (error) {
                this.log('[Deepgram] Error closing WebSocket:', error);
            }
            this.deepgramWs = null;
        }

        if (this.sock) {
            this.sock.removeAllListeners('message');
            try {
                this.sock.close();
            } catch (error) {
                this.log('[RTP] Error closing socket:', error);
            }
            this.sock = null;
        }

        this.outboundQueue = [];
        this.isProcessingQueue = false;
        this.mulawRemainder = Buffer.alloc(0);

        releaseRtpPort(this.dport);
        releaseRtpPort(this.port);
        this.dport = undefined;
        this.port = undefined;
        this.socketReady = false;
        this.log('[Channel] Cleanup finished.');
    }
}

server.on('connection', async (call, { headers, body, data, uuid }) => {
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

    call.on('CHANNEL_ANSWER', async function ({ headers, body }) {
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

    call.on('CHANNEL_HANGUP_COMPLETE', function () {
        cleanupChannel('CHANNEL_HANGUP_COMPLETE');
    });

    call.on('CHANNEL_DESTROY', function () {
        cleanupChannel('CHANNEL_DESTROY');
    });

    call.once('freeswitch_disconnect', function () {
        cleanupChannel('freeswitch_disconnect');
    });
});

server.listen({ port: 8085 });
