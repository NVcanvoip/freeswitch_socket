const WebSocket = require('ws');

// Создаем WebSocket сервер на порту 8765
const wss = new WebSocket.Server({ port: 8765 });
const VOICE_PATH_PREFIX = '/voice/fs/v1';

wss.on('connection', (ws, request) => {
  const requestPath = request?.url || '';
  const isVoicePath = requestPath.startsWith(VOICE_PATH_PREFIX);

  if (isVoicePath) {
    console.log('[Voice] Client connected with path:', requestPath);

    let readySent = false;
    let acknowledgmentReceived = false;
    let clearTimer = null;
    let endCallTimer = null;

    const logPayload = (direction, payload) => {
      let printable = '';
      if (typeof payload === 'string') {
        printable = payload;
      } else if (Buffer.isBuffer(payload)) {
        printable = payload.toString('utf8');
      } else {
        try {
          printable = JSON.stringify(payload);
        } catch (error) {
          printable = '[unserializable payload]';
        }
      }

      console.log(`[Voice] ${direction}: ${printable}`);
    };

    const sendJsonPayload = (payload) => {
      try {
        const serialized = JSON.stringify(payload);
        ws.send(serialized);
        logPayload('Sent', serialized);
      } catch (error) {
        console.error('[Voice] Failed to send JSON payload:', error);
      }
    };

    const sendReadyMessage = () => {
      if (readySent) {
        return;
      }

      readySent = true;
      const payload = {
        type: 'ready_to_greet',
        timestamp: Math.floor(Date.now() / 1000),
      };

      sendJsonPayload(payload);
    };

    const scheduleControlMessages = () => {
      if (clearTimer) {
        return;
      }

      clearTimer = setTimeout(() => {
        const clearPayload = {
          type: 'clear',
          timestamp: Math.floor(Date.now() / 1000),
        };
        sendJsonPayload(clearPayload);

        endCallTimer = setTimeout(() => {
          const endCallPayload = {
            type: 'end_call',
            reason: 'no_response',
            timestamp: Math.floor(Date.now() / 1000),
          };
          sendJsonPayload(endCallPayload);
        }, 5000);
      }, 10000);
    };

    const readyTimer = setTimeout(sendReadyMessage, 5000);

    ws.on('message', (message) => {
      logPayload('Received', message);

      if (!acknowledgmentReceived) {
        let parsed;

        if (typeof message === 'string') {
          try {
            parsed = JSON.parse(message);
          } catch (error) {
            parsed = null;
          }
        } else if (Buffer.isBuffer(message)) {
          try {
            parsed = JSON.parse(message.toString('utf8'));
          } catch (error) {
            parsed = null;
          }
        }

        if (parsed && parsed.type === 'acknowledgment') {
          acknowledgmentReceived = true;
          console.log('[Voice] acknowledgment received from client');
          scheduleControlMessages();
          return;
        }
      }

      if (acknowledgmentReceived) {
        ws.send(message);
        logPayload('Sent', message);
      }
    });

    const handleCleanup = () => {
      clearTimeout(readyTimer);
      if (clearTimer) {
        clearTimeout(clearTimer);
        clearTimer = null;
      }
      if (endCallTimer) {
        clearTimeout(endCallTimer);
        endCallTimer = null;
      }
      console.log('[Voice] Client disconnected');
    };

    ws.on('close', handleCleanup);
    ws.on('error', handleCleanup);
  } else {
    console.log('Client connected');

    ws.on('message', (message) => {
      console.log('Received message:', message);
      setTimeout(() => {
        ws.send(message);
        console.log('Sent message back to client');
      }, 1000);
    });

    ws.on('close', () => {
      console.log('Client disconnected');
    });
  }
});

console.log('WebSocket server is running on ws://localhost:8765');
