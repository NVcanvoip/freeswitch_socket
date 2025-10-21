const { FreeSwitchServer } = require('esl');
const dgram = require('dgram');
const fs = require('fs');
const fsPromises = fs.promises;
const path = require('path');
const { setTimeout } = require('timers/promises');

const FRAME_SIZE = 160; // 20 ms at 8 kHz
const BYTES_PER_SAMPLE = 2; // 16-bit PCM
const FRAME_BYTES = FRAME_SIZE * BYTES_PER_SAMPLE; // 320 bytes per frame
const FRAME_INTERVAL_MS = 10; // 50 packets per second
const WAV_HEADER_BYTES = 44;

// Update this path to point to the WAV file that should be streamed to the call.
const PLAYBACK_FILE_PATH = process.env.PLAYBACK_FILE_PATH || path.join(__dirname, 'playback_audio.wav');

const server = new FreeSwitchServer();
const channels = {};

const RTP_PORT_MIN = 40000;
const RTP_PORT_MAX = 50000;
const allocatedPorts = new Set();

function allocateRtpPort() {
    for (let port = RTP_PORT_MIN; port <= RTP_PORT_MAX; port += 1) {
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
    constructor({ playbackFilePath } = {}) {
        this.sock = dgram.createSocket('udp4');
        this.socketReady = false;
        this.outboundQueue = [];
        this.isFlushingOutbound = false;
        this.pendingOutboundBuffer = Buffer.alloc(0);
        this.uuid = null;
        this.port = undefined;
        this.dport = undefined;
        this.rtpAdress = undefined;
        this.playbackFilePath = playbackFilePath || PLAYBACK_FILE_PATH;
        this.playbackStream = null;

        this.sock.on('listening', () => {
            this.socketReady = true;
            this.flushOutboundQueue();
        });

        this.sock.on('error', (error) => {
            console.error('[RTP] Socket error:', error);
            this.socketReady = false;
            try {
                this.sock.close();
            } catch (closeError) {
                console.error('[RTP] Error closing socket after error:', closeError);
            }
        });

        this.sock.on('close', () => {
            this.socketReady = false;
            this.outboundQueue = [];
            this.isFlushingOutbound = false;
            this.pendingOutboundBuffer = Buffer.alloc(0);
            releaseRtpPort(this.dport);
            releaseRtpPort(this.port);
            this.dport = undefined;
            this.port = undefined;
            this.sock = null;
        });
    }

    enqueueOutbound(buffer) {
        if (!buffer || buffer.length === 0) {
            return;
        }

        const normalizedBuffer = Buffer.isBuffer(buffer) ? buffer : Buffer.from(buffer);
        this.outboundQueue.push(normalizedBuffer);
        this.flushOutboundQueue();
    }

    flushOutboundQueue() {
        if (this.isFlushingOutbound) {
            return;
        }

        if (!this.sock || !this.socketReady || this.port === undefined || !this.rtpAdress) {
            return;
        }

        this.isFlushingOutbound = true;

        const sendFrames = async () => {
            try {
                while (this.sock && this.socketReady && this.port !== undefined && this.rtpAdress) {
                    while (this.pendingOutboundBuffer.length < FRAME_BYTES) {
                        const nextChunk = this.outboundQueue.shift();
                        if (!nextChunk) {
                            return;
                        }

                        this.pendingOutboundBuffer = this.pendingOutboundBuffer.length === 0
                            ? nextChunk
                            : Buffer.concat([this.pendingOutboundBuffer, nextChunk]);
                    }

                    const frame = this.pendingOutboundBuffer.subarray(0, FRAME_BYTES);
                    this.pendingOutboundBuffer = this.pendingOutboundBuffer.subarray(FRAME_BYTES);

                    await this.sendPcmFrame(frame);
                    await setTimeout(FRAME_INTERVAL_MS);
                }
            } catch (error) {
                console.error('[RTP] Error sending PCM frame:', error);
            } finally {
                this.isFlushingOutbound = false;

                if ((this.pendingOutboundBuffer.length >= FRAME_BYTES || this.outboundQueue.length > 0)
                    && this.sock && this.socketReady && this.port !== undefined && this.rtpAdress) {
                    this.flushOutboundQueue();
                }
            }
        };

        sendFrames();
    }

    sendPcmFrame(frame) {
        return new Promise((resolve, reject) => {
            if (!this.sock || !this.socketReady || this.port === undefined || !this.rtpAdress) {
                return reject(new Error('Socket unavailable for sending PCM frame'));
            }

            this.sock.send(frame, this.port, this.rtpAdress, (error) => {
                if (error) {
                    reject(error);
                } else {
                    resolve();
                }
            });
        });
    }

    async startPlayback() {
        if (!this.playbackFilePath) {
            console.warn('[Playback] No playback file configured.');
            return;
        }

        try {
            await fsPromises.access(this.playbackFilePath, fs.constants.R_OK);
        } catch (error) {
            console.error('[Playback] Unable to access playback file:', this.playbackFilePath, error);
            return;
        }

        console.log(`[Playback] Starting playback for call ${this.uuid || 'unknown'} using file ${this.playbackFilePath}`);
        this.playbackStream = fs.createReadStream(this.playbackFilePath, { start: WAV_HEADER_BYTES });

        this.playbackStream.on('data', (chunk) => {
            if (!chunk || chunk.length === 0) {
                return;
            }
            this.enqueueOutbound(chunk);
        });

        this.playbackStream.on('end', () => {
            if (this.pendingOutboundBuffer.length > 0) {
                const paddedFrame = Buffer.alloc(FRAME_BYTES, 0);
                this.pendingOutboundBuffer.copy(paddedFrame, 0, 0, Math.min(this.pendingOutboundBuffer.length, FRAME_BYTES));
                this.pendingOutboundBuffer = Buffer.alloc(0);
                this.enqueueOutbound(paddedFrame);
            }
            console.log(`[Playback] Completed playback for call ${this.uuid || 'unknown'}`);
        });

        this.playbackStream.on('error', (error) => {
            console.error('[Playback] Stream error:', error);
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
        this.rtpAdress = '127.0.0.1';
        this.sock.bind(this.dport);

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

        await this.startPlayback();
    }

    cleanup() {
        if (this.playbackStream) {
            try {
                this.playbackStream.destroy();
            } catch (error) {
                console.error('[Playback] Error destroying playback stream:', error);
            }
            this.playbackStream = null;
        }

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
        this.isFlushingOutbound = false;
        this.pendingOutboundBuffer = Buffer.alloc(0);

        releaseRtpPort(this.dport);
        releaseRtpPort(this.port);
        this.dport = undefined;
        this.port = undefined;
        this.socketReady = false;
    }
}

server.on('connection', async (call, { uuid }) => {
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

    call.on('CHANNEL_ANSWER', async () => {
        console.log('Call was answered');
        const fsChannel = new Channel({ playbackFilePath: PLAYBACK_FILE_PATH });
        await fsChannel.init(call, uuid);
        channels[uuid] = fsChannel;
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

server.listen({ port: 8085 });
