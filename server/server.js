// server/server.js
const express = require('express');
const http = require('http');
const socketIO = require('socket.io');

// Inicializace serveru
const app = express();
const server = http.createServer(app);
const io = socketIO(server);

// Statické soubory
app.use(express.static('public'));

// Herní stav
let players = {};

io.on('connection', (socket) => {
  console.log(`Nový hráč připojen: ${socket.id}`);

  // Přidání nového hráče do hry
  players[socket.id] = {
    x: Math.random() * 800,
    y: Math.random() * 600,
    angle: 0,
    speed: 0,
  };

  // Odeslání aktuálního stavu novému hráči
  socket.emit('updatePlayers', players);

  // Informování ostatních hráčů o novém hráči
  socket.broadcast.emit('updatePlayers', players);

  // Přijetí vstupu od klienta
  socket.on('movement', (data) => {
    const player = players[socket.id];
    if (player) {
      if (data.left) {
        player.angle -= 0.05;
      }
      if (data.right) {
        player.angle += 0.05;
      }
      if (data.up) {
        player.x += Math.cos(player.angle) * 5;
        player.y += Math.sin(player.angle) * 5;
      }
      players[socket.id] = player;

      // Odeslání aktualizovaného stavu všem hráčům
      io.emit('updatePlayers', players);
    }
  });

  // Odpojení hráče
  socket.on('disconnect', () => {
    console.log(`Hráč odpojen: ${socket.id}`);
    delete players[socket.id];
    io.emit('updatePlayers', players);
  });
});

// Spuštění serveru
server.listen(3000, () => {
  console.log('Server běží na portu 3000');
});
