const { FreeSwitchServer, once } = require('esl');
const WebSocket = require('ws');
const dgram = require('dgram');
const fs = require('fs');
const path = require('path');
const EventEmitter = require('events');
const { setTimeout } = require('timers/promises');
const { v4: uuidv4 } = require('uuid');


api_key = "aaa"
//deepgram_ws_url = "wss://api.deepgram.com/v1/listen?punctuate=true&model=nova-2&language=ru&sample_rate=8000&encoding=mulaw&smart_format=true&interim_results=true&utterance_end_ms=1000&vad_events=true&endpointing=300"
deepgram_ws_url = "wss://api.deepgram.com/v1/listen?punctuate=true&model=nova-2&language=ru&sample_rate=8000&encoding=mulaw&smart_format=true&interim_results=true&utterance_end_ms=1000&vad_events=true&endpointing=300"
//deepgram_ws_url = "wss://tester2.mobilon.ru:8765";

header = {
    "Authorization": "Token " + api_key
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
    throw new Error('Нет доступных RTP портов в заданном диапазоне');
}

function releaseRtpPort(port) {
    if (port !== undefined) {
        allocatedPorts.delete(port);
    }
}


class Channel {
    constructor() {
        this.ssrc = Math.floor(Math.random() * 0xFFFFFFFF);
        this.seqNum = 0;
        this.timestamp = 0;
        this.sock = dgram.createSocket('udp4');
        this.socketReady = false;
        this.bufferQueue = new EventEmitter();
        this.bufferQueue.setMaxListeners(100);
        this.port = undefined;
        this.dport = undefined;

        this.sock.on('listening', () => {
            this.socketReady = true;
        });

        this.sock.on('error', (error) => {
            console.error('[RTP] Ошибка сокета:', error);
            this.socketReady = false;
            this.sock.close();
        });

        this.sock.on('close', () => {
            this.socketReady = false;
            releaseRtpPort(this.dport);
            releaseRtpPort(this.port);
            this.dport = undefined;
            this.port = undefined;
            this.sock = null;
        });
    }


    receiveAudio() {
    this.sock.on('message', (message, client) => {
        if (message.length < 12) {
            console.log("Ошибка: полученный пакет меньше 12 байт!");
            return;
        }
            this.bufferQueue.emit('data', message);
        });
    }


    sendAudioSTT() {
        // Initialize Deepgram WebSocket
	console.log("Console Log TRY Connected");

        this.deepgramWs = new WebSocket(deepgram_ws_url, { headers: header });

    this.deepgramWs.on("open", () => {
	    console.log("[Deepgram] Connected");
//	    deepgramWs.send(JSON.stringify({ event: 'connected' }));
	    this.bufferQueue.on('data', (audioData) => {
	    if (!audioData) return;
//        	const audioData2 = audioData.toString('base64');
//            	console.log('Send AUDIO');
	    this.deepgramWs.send(audioData);

	    });
	});

        this.deepgramWs.on("message", async (message) => {
        try {
            const response = JSON.parse(message);
                console.log("[Deepgram] Response received: ", response.is_final , response.speech_final);
	if (response.is_final === true || response.speech_final === true) {
	     const transcript = response.channel.alternatives[0].transcript;
	     console.log("Transcript: ", transcript);
	}
                console.log("[Deepgram] Response received: ",JSON.stringify(response, null, 2));
                console.log(response);
//                console.log(response.channel.alternatives[0].transcript);
	    } catch (error) {
    	console.error("[Deepgram] Error processing message: ", error, "Message: ", message);
	    }
    });
    }


    sendAudio(address, port) {
        this.bufferQueue.on('data', (audioData) => {
            if (!audioData) {
                return;
            }

            const rtpPacket = audioData;
            setTimeout(1000).then(() => {
                if (!this.sock || !this.socketReady) {
                    console.warn('[RTP] Попытка отправить пакет при неактивном сокете, пакет отброшен');
                    return;
                }

                try {
                    this.sock.send(rtpPacket, port, address, (error) => {
                        if (error) {
                            console.error('[RTP] Ошибка отправки пакета:', error);
                        }
                    });
                } catch (error) {
                    if (error && error.code === 'ERR_SOCKET_DGRAM_NOT_RUNNING') {
                        console.warn('[RTP] Попытка отправить через остановленный сокет:', error.message || error);
                    } else {
                        console.error('[RTP] Непредвиденная ошибка отправки пакета:', error);
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
            flags: 'native'
          });
          console.log('Unicast result:', result);
    } catch (error) {
          console.error('Unicast error:', error);
    }

//        await setTimeout(3000); // for echo test
        this.sendAudio(this.rtpAdress, this.port); // for echo test
//        this.sendAudioSTT(); // to DEEPGRAM!!!!
    }

    cleanup() {
        this.bufferQueue.removeAllListeners();
        if (this.deepgramWs) {
            try {
                this.deepgramWs.close();
            } catch (error) {
                console.error('[Deepgram] Ошибка при закрытии WebSocket:', error);
            }
            this.deepgramWs = null;
        }

        if (this.sock) {
            this.sock.removeAllListeners('message');
            try {
                this.sock.close();
            } catch (error) {
                console.error('[RTP] Ошибка при закрытии сокета:', error);
            }
        }

        releaseRtpPort(this.dport);
        this.dport = undefined;
    }

    cleanup() {
        this.bufferQueue.removeAllListeners();
        if (this.deepgramWs) {
            try {
                this.deepgramWs.close();
            } catch (error) {
                console.error('[Deepgram] Ошибка при закрытии WebSocket:', error);
            }
            this.deepgramWs = null;
        }

        if (this.sock) {
            this.sock.removeAllListeners('message');
            try {
                this.sock.close();
            } catch (error) {
                console.error('[RTP] Ошибка при закрытии сокета:', error);
            }
            this.sock = null;
        }

        releaseRtpPort(this.dport);
        this.dport = undefined;
        this.socketReady = false;
    }

    cleanup() {
        this.bufferQueue.removeAllListeners();
        if (this.deepgramWs) {
            try {
                this.deepgramWs.close();
            } catch (error) {
                console.error('[Deepgram] Ошибка при закрытии WebSocket:', error);
            }
            this.deepgramWs = null;
        }

        if (this.sock) {
            this.sock.removeAllListeners('message');
            try {
                this.sock.close();
            } catch (error) {
                console.error('[RTP] Ошибка при закрытии сокета:', error);
            }
            this.sock = null;
        }

        releaseRtpPort(this.dport);
        releaseRtpPort(this.port);
        this.dport = undefined;
        this.port = undefined;
        this.socketReady = false;
    }
}


server.on('connection', async (call ,{headers, body, data, uuid}) => {
  console.log('AAAAAAAAAAAAAAAAAAAAAAAAA ',uuid);
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
    console.log('11111111111Call was answered');
     const fsChannel = new Channel();
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
