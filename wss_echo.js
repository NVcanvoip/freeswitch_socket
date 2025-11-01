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

    const sendReadyMessage = () => {
      if (readySent) {
        return;
      }

      readySent = true;
      const payload = {
        type: 'ready_to_greet',
        timestamp: Math.floor(Date.now() / 1000),
      };

      try {
        const serialized = JSON.stringify(payload);
        ws.send(serialized);
        console.log('[Voice] Sent ready_to_greet payload:', serialized);
      } catch (error) {
        console.error('[Voice] Failed to send ready_to_greet payload:', error);
      }
    };

    const readyTimer = setTimeout(sendReadyMessage, 5000);

    ws.on('message', (message) => {
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
          return;
        }
      }

      if (acknowledgmentReceived) {
        ws.send(message);
      }
    });

    const handleCleanup = () => {
      clearTimeout(readyTimer);
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
