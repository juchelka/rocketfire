// server/server.js
require('dotenv').config();
const express = require('express');
const http = require('http');
const socketIO = require('socket.io');
const { performance } = require('perf_hooks');
const { players, projectiles, monsters, stars, createPlayer, createMonster } = require('./gameState');
const { updateGameState } = require('./gameLogic');

// Inicializace serveru
const app = express();
const server = http.createServer(app);
const io = socketIO(server);

// Statické soubory
app.use(express.static('public'));

let cycleCount = 0;

// Generování náhodných hvězd
for (let i = 0; i < 100; i++) {
    stars.push({
        x: Math.random() * 2000 - 1000,
        y: Math.random() * 2000 - 1000,
        size: Math.random() * 3 + 1,
    });
}

// Generování příšer
for (let i = 0; i < 5; i++) {
    monsters.push(createMonster());
}

io.on('connection', (socket) => {
    console.log(`Nový hráč se připojil: ${socket.id}`);

    // Přidání nového hráče do hry
    players[socket.id] = createPlayer(socket.id);

    // Přijetí jména hráče
    socket.on('setName', (name) => {
        if (players[socket.id]) {
            players[socket.id].name = name;
            io.emit('updatePlayers', players);
        }
    });  

    // Odeslání aktuálního stavu novému hráči
    socket.emit('updatePlayers', players);
    socket.emit('starPositions', stars);
    socket.emit('yourId', socket.id);
    socket.emit('updateProjectiles', projectiles);
    socket.emit('updateMonsters', monsters);

    // Informování ostatních hráčů o novém hráči
    socket.broadcast.emit('updatePlayers', players);

    // Zpracování požadavku na synchronizaci času
    socket.on('syncTime', (t0) => {
        const t1 = performance.now();
        socket.emit('syncTimeResponse', t1, t0);
    });

    // Přijímání vstupu od klienta
    socket.on('movement', (data) => {
        const player = players[socket.id];
        if (player) {
            if (data.rotateLeft) {
                player.angle -= 0.05;
            }
            if (data.rotateRight) {
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
            if (data.left) {
                player.x += Math.cos(player.angle - Math.PI / 2) * 2;
                player.y += Math.sin(player.angle - Math.PI / 2) * 2;
            }
            if (data.right) {
                player.x += Math.cos(player.angle + Math.PI / 2) * 2;
                player.y += Math.sin(player.angle + Math.PI / 2) * 2;
            }
        }
    });

    // Zpracování střelby
    let shootingInterval = null;
    socket.on('shoot', (isShooting) => {
        const player = players[socket.id];
        if (isShooting && player) {
            // Okamžitě vystřelit první střelu
            projectiles.push(createProjectile(player.x, player.y, player.angle, socket.id));
            // Zahájit interval pro další střely
            if (!shootingInterval) {
                shootingInterval = setInterval(() => {
                    const currentPlayer = players[socket.id];
                    if (currentPlayer) {
                        projectiles.push(createProjectile(currentPlayer.x, currentPlayer.y, currentPlayer.angle, socket.id));
                    }
                }, 250);
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

// Herní smyčka
setInterval(() => {
    cycleCount++;
    updateGameState();

    // Odeslání aktualizací klientům
    io.emit('updatePlayers', players);
    io.emit('updateProjectiles', projectiles);
    io.emit('updateMonsters', monsters);

}, 1000 / 60); // 60krát za sekundu

// Odeslání diagnostických dat každých 5 sekund
setInterval(() => {
    io.emit('serverDiagnostics', { cycleCount: cycleCount / 5});
    cycleCount = 0;
}, 5000);

// Spuštění serveru
const PORT = process.env.PORT || 3000;
server.listen(PORT, () => {
    console.log(`Server běží na portu ${PORT}`);
});