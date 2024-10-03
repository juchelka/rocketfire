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
let projectiles = [];

// Pozice hvězd
let stars = [];

// Generování náhodných hvězd
for (let i = 0; i < 100; i++) {
  stars.push({
    x: Math.random() * 2000 - 1000,
    y: Math.random() * 2000 - 1000,
    size: Math.random() * 3 + 1, // Velikost mezi 1 a 4
  });
}

// Funkce pro generování náhodné pastelové barvy
function getRandomPastelColor() {
  const r = Math.floor((Math.random() * 127) + 127);
  const g = Math.floor((Math.random() * 127) + 127);
  const b = Math.floor((Math.random() * 127) + 127);
  return `rgb(${r},${g},${b})`;
}

io.on('connection', (socket) => {
  console.log(`Nový hráč se připojil: ${socket.id}`);

  // Přidání nového hráče do hry
  players[socket.id] = {
    x: Math.random() * 800,
    y: Math.random() * 600,
    angle: 0,
    speed: 0,
    color: getRandomPastelColor(),
    score: 0
  };

  // Odeslání aktuálního stavu novému hráči
  socket.emit('updatePlayers', players);
  socket.emit('starPositions', stars);
  socket.emit('yourId', socket.id);
  socket.emit('updateProjectiles', projectiles);

  // Informování ostatních hráčů o novém hráči
  socket.broadcast.emit('updatePlayers', players);

  // Přijímání vstupu od klienta
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
    }
  });

  // Zpracování střelby
  socket.on('shoot', () => {
    const player = players[socket.id];
    if (player) {
      // Počet existujících projektilů hráče
      const playerProjectiles = projectiles.filter(p => p.ownerId === socket.id);
      if (playerProjectiles.length < 3) {
        projectiles.push({
          x: player.x,
          y: player.y,
          startX: player.x, // Uložení počáteční pozice X
          startY: player.y, // Uložení počáteční pozice Y
          angle: player.angle,
          ownerId: socket.id
        });
      }
    }
  });

  // Odpojení hráče
  socket.on('disconnect', () => {
    console.log(`Hráč se odpojil: ${socket.id}`);
    delete players[socket.id];
    io.emit('updatePlayers', players);
  });
});

// Herní smyčka
setInterval(() => {
  // Pohyb projektilů
  for (let i = projectiles.length - 1; i >= 0; i--) {
    const proj = projectiles[i];
    proj.x += Math.cos(proj.angle) * 20; // Rychlost projektilu
    proj.y += Math.sin(proj.angle) * 20;

    // Kontrola kolize s hráči
    for (let id in players) {
      const player = players[id];
      if (id !== proj.ownerId) {
        const dx = proj.x - player.x;
        const dy = proj.y - player.y;
        const distance = Math.sqrt(dx * dx + dy * dy);
        if (distance < 15) { // Kolizní rádius
          // Detekována kolize
          players[proj.ownerId].score += 1;
          projectiles.splice(i, 1);
          break;
        }
      }
    }

    // Odstranění projektilu po překročení dostřelu
    const dxProj = proj.x - proj.startX;
    const dyProj = proj.y - proj.startY;
    const distanceProj = Math.sqrt(dxProj * dxProj + dyProj * dyProj);
    if (distanceProj > 1000) {
      projectiles.splice(i, 1);
      continue;
    }
  }

  // Odeslání aktualizací klientům
  io.emit('updatePlayers', players);
  io.emit('updateProjectiles', projectiles);

}, 1000 / 60); // 60krát za sekundu

// Spuštění serveru
server.listen(3000, () => {
  console.log('Server běží na portu 3000');
});
