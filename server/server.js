// server/server.js
const express = require('express');
const http = require('http');
const socketIO = require('socket.io');

// Initialize server
const app = express();
const server = http.createServer(app);
const io = socketIO(server);

// Static files
app.use(express.static('public'));

// Game state
let players = {};

// Star positions
let stars = [];

// Generate random stars
for (let i = 0; i < 100; i++) {
  stars.push({
    x: Math.random() * 2000 - 1000,
    y: Math.random() * 2000 - 1000,
    size: Math.random() * 2 + 3, // Size between 3 and 5
  });
}

io.on('connection', (socket) => {
  console.log(`New player connected: ${socket.id}`);

  // Add new player to the game
  players[socket.id] = {
    x: Math.random() * 800,
    y: Math.random() * 600,
    angle: 0,
    speed: 0,
  };

  // Send current state to the new player
  socket.emit('updatePlayers', players);
  socket.emit('starPositions', stars);
  socket.emit('yourId', socket.id);

  // Inform other players about the new player
  socket.broadcast.emit('updatePlayers', players);

  // Receive input from client
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
      if (data.down) {
        player.x -= Math.cos(player.angle) * 5;
        player.y -= Math.sin(player.angle) * 5;
      }
      players[socket.id] = player;

      // Send updated state to all players
      io.emit('updatePlayers', players);
    }
  });

  // Player disconnects
  socket.on('disconnect', () => {
    console.log(`Player disconnected: ${socket.id}`);
    delete players[socket.id];
    io.emit('updatePlayers', players);
  });
});

// Start server
server.listen(3000, () => {
  console.log('Server running on port 3000');
});
