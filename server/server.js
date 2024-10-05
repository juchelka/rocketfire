// server/server.js
require('dotenv').config();
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
let monsters = [];

// Pozice hvězd
let stars = [];

// Generování náhodných hvězd
for (let i = 0; i < 100; i++) {
  stars.push(createStar());
}

// Generování příšer
for (let i = 0; i < 5; i++) {
  spawnNewMonster();
}

// Funkce pro generování náhodné pastelové barvy
function getRandomPastelColor() {
  const r = Math.floor((Math.random() * 127) + 127);
  const g = Math.floor((Math.random() * 127) + 127);
  const b = Math.floor((Math.random() * 127) + 127);
  return `rgb(${r},${g},${b})`;
}

// Funkce pro generování nové příšery
function spawnNewMonster() {
  monsters.push(createMonster());
}

// Funkce pro vytvoření nové příšery
function createMonster() {
  return {
    x: Math.random() * 2000 - 1000,
    y: Math.random() * 2000 - 1000,
    health: 3, // Nová příšera má 3 životy
  };
}

// Funkce pro vytvoření nové hvězdy
function createStar() {
  return {
    x: Math.random() * 2000 - 1000,
    y: Math.random() * 2000 - 1000,
    size: Math.random() * 3 + 1, // Velikost mezi 1 a 4
  };
}

// Funkce pro vytvoření nového projektilu
function createProjectile(player) {
  return {
    x: player.x,
    y: player.y,
    startX: player.x,
    startY: player.y,
    angle: player.angle,
    ownerId: player.id,
  };
}

io.on('connection', (socket) => {
  console.log(`Nový hráč se připojil: ${socket.id}`);

  // Přidání nového hráče do hry
  players[socket.id] = createPlayer(socket.id);

  // Odeslání aktuálního stavu novému hráči
  socket.emit('updatePlayers', players);
  socket.emit('starPositions', stars);
  socket.emit('yourId', socket.id);
  socket.emit('updateProjectiles', projectiles);
  socket.emit('updateMonsters', monsters);

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
    }
  });

  // Zpracování střelby
  let shootingInterval = null;
  socket.on('shoot', (isShooting) => {
    const player = players[socket.id];
    if (isShooting && player) {
      // Okamžitě vystřelit první střelu
      shootProjectile(player);
      // Zahájit interval pro další střely
      if (!shootingInterval) {
        shootingInterval = setInterval(() => {
          const currentPlayer = players[socket.id];
          if (currentPlayer) {
            shootProjectile(currentPlayer);
          }
        }, 250); // 4 střely za sekundu (250 ms mezi střelami)
      }
    } else {
      clearInterval(shootingInterval);
      shootingInterval = null;
    }
  });

  // Odpojení hráče
  socket.on('disconnect', () => {
    console.log(`Hráč se odpojil: ${socket.id}`);
    delete players[socket.id];
    if (shootingInterval) {
      clearInterval(shootingInterval);
    }
    io.emit('updatePlayers', players);
  });
});

// Funkce pro vytvoření nového hráče
function createPlayer(id) {
  return {
    id: id,
    x: Math.random() * 800,
    y: Math.random() * 600,
    angle: 0,
    speed: 0,
    color: getRandomPastelColor(),
    score: 0,
  };
}

// Funkce pro vystřelení projektilu
function shootProjectile(player) {
  projectiles.push(createProjectile(player));
}

// Herní smyčka
setInterval(() => {
  // Pohyb projektilů
  for (let i = projectiles.length - 1; i >= 0; i--) {
    const proj = projectiles[i];
    proj.x += Math.cos(proj.angle) * 20;
    proj.y += Math.sin(proj.angle) * 20;

    // Kontrola kolize s příšerami
    for (let j = monsters.length - 1; j >= 0; j--) {
      const monster = monsters[j];
      const dx = proj.x - monster.x;
      const dy = proj.y - monster.y;
      const distance = Math.sqrt(dx * dx + dy * dy);
      if (distance < 20) {
        // Zásah příšery
        monster.health -= 1;
        if (monster.health <= 0) {
          monsters.splice(j, 1);
          // Zvýšení skóre hráče, který příšeru zasáhl
          if (players[proj.ownerId]) {
            players[proj.ownerId].score += 1;
          }
          // Spawn nové příšery
          spawnNewMonster();
        }
        projectiles.splice(i, 1);
        break;
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

  // Pohyb příšer směrem k náhodnému bodu
  for (let monster of monsters) {
    monster.x += (Math.random() - 0.5) * 2;
    monster.y += (Math.random() - 0.5) * 2;
  }

  // Odeslání aktualizací klientům
  io.emit('updatePlayers', players);
  io.emit('updateProjectiles', projectiles);
  io.emit('updateMonsters', monsters);

}, 1000 / 60); // 60krát za sekundu

// Spuštění serveru
const PORT = process.env.PORT || 3000;
server.listen(PORT, () => {
  console.log(`Server běží na portu ${PORT}`);
});
