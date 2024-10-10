<?php

function updateFile($filename, $search, $replace, $expectedCount = 1) {
    $content = file_get_contents($filename);
    $newContent = str_replace($search, $replace, $content, $count);
    if ($count !== $expectedCount) {
        fwrite(STDERR, "\nReplacement in file $filename \n-------\n$search\n--- by ---\n$replace\n-------\nfailed. Found $count occurrences instead of $expectedCount.\n");
        return false;
    } else {
        file_put_contents($filename, $newContent);
        echo "Updated file $filename ($count replacements)\n";  
        return true;
    }
}

function createFile($filename, $content) {
    if (file_put_contents($filename, $content) !== false) {
        echo "Created file $filename\n";
        return true;
    } else {
        fwrite(STDERR, "\nFailed to create file $filename\n");
        return false;
    }
}

$allDone = true;

// Create server modules
$allDone &= createFile('server/gameState.js', <<<'EOD'
// server/gameState.js
let players = {};
let projectiles = [];
let monsters = [];
let stars = [];

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

function createMonster() {
    return {
        x: Math.random() * 2000 - 1000,
        y: Math.random() * 2000 - 1000,
        health: 3,
        maxHealth: 3,
        lastShot: 0,
        shootInterval: Math.floor(Math.random() * 5000) + 3000,
    };
}

function createProjectile(x, y, angle, ownerId) {
    return {
        x: x,
        y: y,
        startX: x,
        startY: y,
        angle: angle,
        ownerId: ownerId,
        speed: ownerId === 'monster' ? 5 : 20,
    };
}

function getRandomPastelColor() {
    const r = Math.floor((Math.random() * 127) + 127);
    const g = Math.floor((Math.random() * 127) + 127);
    const b = Math.floor((Math.random() * 127) + 127);
    return `rgb(${r},${g},${b})`;
}

module.exports = {
    players,
    projectiles,
    monsters,
    stars,
    createPlayer,
    createMonster,
    createProjectile,
    getRandomPastelColor
};
EOD);

$allDone &= createFile('server/gameLogic.js', <<<'EOD'
// server/gameLogic.js
const { players, projectiles, monsters, createMonster, createProjectile } = require('./gameState');

function updateGameState() {
    updateProjectiles();
    updateMonsters();
}

function updateProjectiles() {
    for (let i = projectiles.length - 1; i >= 0; i--) {
        const proj = projectiles[i];
        proj.x += Math.cos(proj.angle) * proj.speed;
        proj.y += Math.sin(proj.angle) * proj.speed;

        if (handleProjectileCollisions(proj, i)) continue;

        if (isProjectileOutOfRange(proj)) {
            projectiles.splice(i, 1);
        }
    }
}

function handleProjectileCollisions(proj, index) {
    // Check collision with monsters
    for (let j = monsters.length - 1; j >= 0; j--) {
        const monster = monsters[j];
        if (checkCollision(proj, monster, 20) && proj.ownerId !== 'monster') {
            handleMonsterHit(monster, j, proj.ownerId);
            projectiles.splice(index, 1);
            return true;
        }
    }

    // Check collision with players (only for monster projectiles)
    if (proj.ownerId === 'monster') {
        for (let playerId in players) {
            const player = players[playerId];
            if (checkCollision(proj, player, 20)) {
                handlePlayerHit(player);
                projectiles.splice(index, 1);
                return true;
            }
        }
    }

    return false;
}

function checkCollision(obj1, obj2, distance) {
    const dx = obj1.x - obj2.x;
    const dy = obj1.y - obj2.y;
    return Math.sqrt(dx * dx + dy * dy) < distance;
}

function handleMonsterHit(monster, index, playerId) {
    monster.health -= 1;
    if (monster.health <= 0) {
        monsters.splice(index, 1);
        if (players[playerId]) {
            players[playerId].score += 1;
            players[playerId].monsterHits = (players[playerId].monsterHits || 0) + 1;
        }
        monsters.push(createMonster());
    }
}

function handlePlayerHit(player) {
    player.hits = (player.hits || 0) + 1;
    player.score = (player.monsterHits || 0) - player.hits;
}

function isProjectileOutOfRange(proj) {
    const dx = proj.x - proj.startX;
    const dy = proj.y - proj.startY;
    return Math.sqrt(dx * dx + dy * dy) > 1000;
}

function updateMonsters() {
    const currentTime = Date.now();
    for (let monster of monsters) {
        monster.x += (Math.random() - 0.5) * 2;
        monster.y += (Math.random() - 0.5) * 2;

        if (currentTime - monster.lastShot > monster.shootInterval) {
            const closestPlayer = findClosestPlayer(monster);
            if (closestPlayer) {
                const angle = Math.atan2(closestPlayer.y - monster.y, closestPlayer.x - monster.x);
                projectiles.push(createProjectile(monster.x, monster.y, angle, 'monster'));
                monster.lastShot = currentTime;
                monster.shootInterval = Math.floor(Math.random() * 5000) + 3000;
            }
        }
    }
}

function findClosestPlayer(monster) {
    let closestPlayer = null;
    let closestDistance = Infinity;
    for (let playerId in players) {
        const player = players[playerId];
        const distance = Math.sqrt(Math.pow(player.x - monster.x, 2) + Math.pow(player.y - monster.y, 2));
        if (distance < closestDistance) {
            closestDistance = distance;
            closestPlayer = player;
        }
    }
    return closestPlayer;
}

module.exports = { updateGameState };
EOD);

// Update server.js
$serverSearch = file_get_contents('server/server.js');
$serverReplace = <<<'EOD'
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
EOD;

$allDone &= updateFile('server/server.js', $serverSearch, $serverReplace);

// Create client modules
$allDone &= createFile('public/gameState.js', <<<'EOD'
// public/gameState.js
export let players = {};
export let projectiles = [];
export let monsters = [];
export let stars = [];
export let myId = null;
export let ping = 0;
export let cycleCount = 0;
export let timeOffset = 0;

export function updatePlayers(serverPlayers) {
    players = JSON.parse(JSON.stringify(serverPlayers));
}

export function updateProjectiles(serverProjectiles) {
    projectiles = serverProjectiles;
}

export function updateStars(serverStars) {
    stars = serverStars;
}

export function updateMonsters(serverMonsters) {
    monsters = serverMonsters;
}

export function updateDiagnostics(data) {
    cycleCount = data.cycleCount;
}

export function updateTimeOffset(serverTime, t0, t2) {
    const RTT = t2 - t0;
    const estimatedServerTime = serverTime + RTT / 2;
    timeOffset = estimatedServerTime - t2;
    ping = RTT / 2;
}

export function getServerTime() {
    return performance.now() + timeOffset;
}
EOD);

$allDone &= createFile('public/input.js', <<<'EOD'
// public/input.js
export const keys = {};

export function setupInputListeners(socket) {
    document.addEventListener('keydown', (event) => {
        if (event.code === 'ArrowUp' || event.code === 'KeyW') keys.up = true;
        if (event.code === 'KeyA') keys.left = true;
        if (event.code === 'KeyD') keys.right = true;
        if (event.code === 'ArrowLeft') keys.rotateLeft = true;
        if (event.code === 'ArrowRight') keys.rotateRight = true;
        if (event.code === 'ArrowDown' || event.code === 'KeyS') keys.down = true;
        if (event.code === 'Space' && keys.space !== true) {
            keys.space = true;
            socket.emit('shoot', true);
        }
    });

    document.addEventListener('keyup', (event) => {
        if (event.code === 'ArrowUp' || event.code === 'KeyW') keys.up = false;
        if (event.code === 'KeyA') keys.left = false;
        if (event.code === 'KeyD') keys.right = false;
        if (event.code === 'ArrowLeft') keys.rotateLeft = false;
        if (event.code === 'ArrowRight') keys.rotateRight = false;
        if (event.code === 'ArrowDown' || event.code === 'KeyS') keys.down = false;
        if (event.code === 'Space') {
            keys.space = false;
            socket.emit('shoot', false);
        }
    });
}

export function sendInput(socket) {
    socket.emit('movement', keys);
    requestAnimationFrame(() => sendInput(socket));
}
EOD);

$allDone &= createFile('public/render.js', <<<'EOD'
// public/render.js
import { players, projectiles, monsters, stars, myId, ping, cycleCount } from './gameState.js';

export function gameLoop(canvas, context) {
    context.clearRect(0, 0, canvas.width, canvas.height);

    const camera = getCameraPosition();
    drawStars(context, camera);
    drawProjectiles(context, camera);
    drawMonsters(context, camera);
    drawPlayers(context, camera);
    drawOffscreenPlayerArrows(canvas, context, camera);
    displayScores();
    displayDiagnostics(context);

    requestAnimationFrame(() => gameLoop(canvas, context));
}

function getCameraPosition() {
    let cameraX = 0;
    let cameraY = 0;
    if (myId && players[myId]) {
        cameraX = players[myId].x - canvas.width / 2;
        cameraY = players[myId].y - canvas.height / 2;
    }
    return { x: cameraX, y: cameraY };
}

function drawStars(context, camera) {
    for (let star of stars) {
        context.fillStyle = 'lightgray';
        context.beginPath();
        context.arc(star.x - camera.x, star.y - camera.y, star.size, 0, Math.PI * 2);
        context.fill();
    }
}

function drawProjectiles(context, camera) {
    context.save();
    for (let proj of projectiles) {
        context.translate(proj.x - camera.x, proj.y - camera.y);
        context.rotate(proj.angle);
        context.fillStyle = proj.ownerId === 'monster' ? 'yellow' : 'red';
        context.beginPath();
        context.moveTo(5, 0);
        context.lineTo(-5, -3);
        context.lineTo(-5, 3);
        context.closePath();
        context.fill();
        context.setTransform(1, 0, 0, 1, 0, 0);
    }
    context.restore();
}

function drawMonsters(context, camera) {
    for (let monster of monsters) {
        context.fillStyle = 'green';
        context.beginPath();
        context.arc(monster.x - camera.x, monster.y - camera.y, 20, 0, Math.PI * 2);
        context.fill();

        const barWidth = 40;
        const barHeight = 5;
        const healthPercentage = monster.health / monster.maxHealth;
        context.fillStyle = 'red';
        context.fillRect(monster.x - camera.x - barWidth / 2, monster.y - camera.y - 30, barWidth, barHeight);
        context.fillStyle = 'green';
        context.fillRect(monster.x - camera.x - barWidth / 2, monster.y - camera.y - 30, barWidth * healthPercentage, barHeight);
    }
}

function drawPlayers(context, camera) {
    for (let id in players) {
        const player = players[id];
        const screenX = player.x - camera.x;
        const screenY = player.y - camera.y;

        if (screenX + 20 < 0 || screenX - 20 > canvas.width || screenY + 20 < 0 || screenY - 20 > canvas.height) {
            continue;
        }

        context.save();
        context.translate(screenX, screenY);
        context.rotate(player.angle);
        context.fillStyle = player.color || 'white';
        context.beginPath();
        context.moveTo(10, 0);
        context.lineTo(-10, -5);
        context.lineTo(-10, 5);
        context.closePath();
        context.fill();
        context.restore();
    }
}

function drawOffscreenPlayerArrows(canvas, context, camera) {
    for (let id in players) {
        if (id !== myId) {
            const player = players[id];
            const screenX = player.x - camera.x;
            const screenY = player.y - camera.y;

            if (screenX < 0 || screenX > canvas.width || screenY < 0 || screenY > canvas.height) {
                const angleToPlayer = Math.atan2(screenY - canvas.height / 2, screenX - canvas.width / 2);
                const arrowX = canvas.width / 2 + Math.cos(angleToPlayer) * (canvas.width / 2 - 20);
                const arrowY = canvas.height / 2 + Math.sin(angleToPlayer) * (canvas.height / 2 - 20);

                context.save();
                context.globalAlpha = 0.5;
                context.translate(arrowX, arrowY);
                context.rotate(angleToPlayer);
                context.fillStyle = player.color || 'white';
                context.beginPath();
                context.moveTo(10, 0);
                context.lineTo(-10, -5);
                context.lineTo(-10, 5);
                context.closePath();
                context.fill();
                context.restore();
            }
        }
    }
}

function displayScores() {
    const scoreBoard = document.getElementById('scoreBoard');
    scoreBoard.innerHTML = '<h2>Skóre</h2>';
    for (let id in players) {
        const player = players[id];
        const playerName = player.name || (id === myId ? 'Ty' : `Hráč ${id.substring(0, 4)}`);
        const scoreEntry = document.createElement('div');
        scoreEntry.textContent = `${playerName}: ${player.score} (Zásahy M: ${player.monsterHits || 0}, Zásahy H: ${player.hits || 0})`;
        scoreEntry.style.color = player.color || 'white';
        scoreBoard.appendChild(scoreEntry);
    }
}

function displayDiagnostics(context) {
    context.fillStyle = 'white';
    context.font = '16px Arial';
    context.fillText(`Ping: ${ping.toFixed(2)} ms`, 10, 20);
    context.fillText(`Server: ${cycleCount}/s of 60`, 10, 40);
}
EOD);

// Update client.js
$clientSearch = file_get_contents('public/client.js');
$clientReplace = <<<'EOD'
// public/client.js
import { setupInputListeners, sendInput } from './input.js';
import { gameLoop } from './render.js';
import * as gameState from './gameState.js';

const socket = io();
const canvas = document.getElementById('gameCanvas');
const context = canvas.getContext('2d');

function resizeCanvas() {
    canvas.width = canvas.clientWidth;
    canvas.height = canvas.clientHeight;
}
window.addEventListener('resize', resizeCanvas);
resizeCanvas();

setupInputListeners(socket);
sendInput(socket);

function synchronizeTime() {
    const t0 = performance.now();
    socket.emit('syncTime', t0);
}

setInterval(synchronizeTime, 5000);

socket.on('syncTimeResponse', (serverTime, t0) => {
    const t2 = performance.now();
    gameState.updateTimeOffset(serverTime, t0, t2);
});

socket.on('updatePlayers', gameState.updatePlayers);
socket.on('updateProjectiles', gameState.updateProjectiles);
socket.on('starPositions', gameState.updateStars);
socket.on('yourId', (id) => gameState.myId = id);
socket.on('updateMonsters', gameState.updateMonsters);
socket.on('serverDiagnostics', gameState.updateDiagnostics);

const playerName = localStorage.getItem('playerName');
if (playerName) {
    socket.emit('setName', playerName);
} else {
    const name = prompt('Zadejte své jméno:');
    if (name) {
        localStorage.setItem('playerName', name);
        socket.emit('setName', name);
    }
}

gameLoop(canvas, context);

document.getElementById('changeName').addEventListener('click', function() {
    const newName = prompt('Zadejte nové jméno:');
    if (newName) {
        localStorage.setItem('playerName', newName);
        socket.emit('setName', newName);
    }
});
EOD;

$allDone &= updateFile('public/client.js', $clientSearch, $clientReplace);

// Update package.json to include new files
$packageJsonSearch = file_get_contents('package.json');
$packageJsonReplace = str_replace(
    '"main": "server/server.js",',
    '"main": "server/server.js",
    "files": [
        "server/gameState.js",
        "server/gameLogic.js",
        "public/gameState.js",
        "public/input.js",
        "public/render.js"
    ],',
    $packageJsonSearch
);

$allDone &= updateFile('package.json', $packageJsonSearch, $packageJsonReplace);

if ($allDone) {
    echo "\nOK. All changes have been made.\n";
} else {
    fwrite(STDERR, "\nError: Some changes could not be made.\n");
}

// Suggested commit message:
// "Refactor server and client code into modules for better organization and maintainability"
?>