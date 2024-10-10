// public/client.js
import { setupInputListeners, sendInput } from './input.js';
import { gameLoop } from './render.js';
import * as gameState from './gameState.js';

const socket = io();

// Expose socket to window for the change name button
window.socket = socket;
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
socket.on('yourId', (id) => gameState.setMyId(id));
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