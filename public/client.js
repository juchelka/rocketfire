// public/client.js
const socket = io();

// Game variables
const canvas = document.getElementById('gameCanvas');
const context = canvas.getContext('2d');

let players = {};
let keys = {};
let stars = [];
let myId = null;

// Player input
document.addEventListener('keydown', (event) => {
  if (event.code === 'ArrowUp') keys.up = true;
  if (event.code === 'ArrowLeft') keys.left = true;
  if (event.code === 'ArrowRight') keys.right = true;
  if (event.code === 'ArrowDown') keys.down = true;
});

document.addEventListener('keyup', (event) => {
  if (event.code === 'ArrowUp') keys.up = false;
  if (event.code === 'ArrowLeft') keys.left = false;
  if (event.code === 'ArrowRight') keys.right = false;
  if (event.code === 'ArrowDown') keys.down = false;
});

// Send input to server
setInterval(() => {
  socket.emit('movement', keys);
}, 1000 / 60);

// Receive updates from server
socket.on('updatePlayers', (serverPlayers) => {
  players = serverPlayers;
});

socket.on('starPositions', (serverStars) => {
  stars = serverStars;
});

socket.on('yourId', (id) => {
  myId = id;
});

// Main game loop
function gameLoop() {
  context.clearRect(0, 0, canvas.width, canvas.height);

  // Calculate camera position
  let cameraX = 0;
  let cameraY = 0;

  if (myId && players[myId]) {
    cameraX = players[myId].x - canvas.width / 2;
    cameraY = players[myId].y - canvas.height / 2;
  }

  // Draw stars
  for (let star of stars) {
    context.fillStyle = 'yellow';
    context.beginPath();
    context.arc(star.x - cameraX, star.y - cameraY, star.size, 0, Math.PI * 2);
    context.fill();
  }

  // Draw players
  for (let id in players) {
    const player = players[id];
    context.save();
    context.translate(player.x - cameraX, player.y - cameraY);
    context.rotate(player.angle);
    context.fillStyle = 'white';
    context.beginPath();
    context.moveTo(10, 0);
    context.lineTo(-10, -5);
    context.lineTo(-10, 5);
    context.closePath();
    context.fill();
    context.restore();
  }

  requestAnimationFrame(gameLoop);
}

gameLoop();
