// public/client.js
const socket = io();

// Herní proměnné
const canvas = document.getElementById('gameCanvas');
const context = canvas.getContext('2d');

let players = {};
let keys = {};

// Vstup od hráče
document.addEventListener('keydown', (event) => {
  if (event.code === 'ArrowUp') keys.up = true;
  if (event.code === 'ArrowLeft') keys.left = true;
  if (event.code === 'ArrowRight') keys.right = true;
});

document.addEventListener('keyup', (event) => {
  if (event.code === 'ArrowUp') keys.up = false;
  if (event.code === 'ArrowLeft') keys.left = false;
  if (event.code === 'ArrowRight') keys.right = false;
});

// Odesílání vstupu na server
setInterval(() => {
  socket.emit('movement', keys);
}, 1000 / 60);

// Přijetí aktualizace hráčů ze serveru
socket.on('updatePlayers', (serverPlayers) => {
  players = serverPlayers;
});

// Hlavní herní smyčka
function gameLoop() {
  context.clearRect(0, 0, canvas.width, canvas.height);

  for (let id in players) {
    const player = players[id];
    context.save();
    context.translate(player.x, player.y);
    context.rotate(player.angle);
    context.fillStyle = 'white';
    context.beginPath();
    context.moveTo(0, -10);
    context.lineTo(5, 10);
    context.lineTo(-5, 10);
    context.closePath();
    context.fill();
    context.restore();
  }

  requestAnimationFrame(gameLoop);
}

gameLoop();
