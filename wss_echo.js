const WebSocket = require('ws');

// Создаем WebSocket сервер на порту 8765
const wss = new WebSocket.Server({ port: 8765 });

wss.on('connection', (ws) => {
  console.log('Client connected');

  // Обработка сообщений от клиента
  ws.on('message', (message) => {
    console.log('Received message:', message);

    // Задержка в 2 секунды перед отправкой обратно
    setTimeout(() => {
      // Отправка обратно сообщения клиенту
      ws.send(message);
      console.log('Sent message back to client');
    }, 1000); // Задержка в 2000 миллисекунд (2 секунды)
  });

  ws.on('close', () => {
    console.log('Client disconnected');
  });
});

console.log('WebSocket server is running on ws://localhost:8765');
