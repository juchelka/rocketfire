// public/client.js
const socket = io();

// Herní proměnné
const canvas = document.getElementById('gameCanvas');
const context = canvas.getContext('2d');

let players = {};
let keys = {};
let stars = [];
let myId = null;
let projectiles = [];

// Funkce pro změnu velikosti plátna
function resizeCanvas() {
  canvas.width = canvas.clientWidth;
  canvas.height = canvas.clientHeight;
}
window.addEventListener('resize', resizeCanvas);
resizeCanvas();

// Vstup hráče
document.addEventListener('keydown', (event) => {
  if (event.code === 'ArrowUp') keys.up = true;
  if (event.code === 'ArrowLeft') keys.left = true;
  if (event.code === 'ArrowRight') keys.right = true;
  if (event.code === 'ArrowDown') keys.down = true;
  if (event.code === 'Space') keys.space = true;
});

document.addEventListener('keyup', (event) => {
  if (event.code === 'ArrowUp') keys.up = false;
  if (event.code === 'ArrowLeft') keys.left = false;
  if (event.code === 'ArrowRight') keys.right = false;
  if (event.code === 'ArrowDown') keys.down = false;
  if (event.code === 'Space') keys.space = false;
});

// Odesílání vstupu na server
setInterval(() => {
  socket.emit('movement', keys);
  if (keys.space) {
    socket.emit('shoot');
    keys.space = false; // Zabránění nepřetržité střelbě
  }
}, 1000 / 60);

// Přijímání aktualizací od serveru
socket.on('updatePlayers', (serverPlayers) => {
  players = serverPlayers;
});

socket.on('updateProjectiles', (serverProjectiles) => {
  projectiles = serverProjectiles;
});

socket.on('starPositions', (serverStars) => {
  stars = serverStars;
});

socket.on('yourId', (id) => {
  myId = id;
});

// Hlavní herní smyčka
function gameLoop() {
  context.clearRect(0, 0, canvas.width, canvas.height);

  // Výpočet pozice kamery
  let cameraX = 0;
  let cameraY = 0;

  if (myId && players[myId]) {
    cameraX = players[myId].x - canvas.width / 2;
    cameraY = players[myId].y - canvas.height / 2;
  }

  // Kreslení hvězd
  for (let star of stars) {
    context.fillStyle = 'yellow';
    context.beginPath();
    context.arc(star.x - cameraX, star.y - cameraY, star.size, 0, Math.PI * 2);
    context.fill();
  }

  // Kreslení projektilů
  for (let proj of projectiles) {
    context.save();
    context.translate(proj.x - cameraX, proj.y - cameraY);
    context.rotate(proj.angle);
    context.fillStyle = 'red';
    context.beginPath();
    context.moveTo(5, 0);
    context.lineTo(-5, -3);
    context.lineTo(-5, 3);
    context.closePath();
    context.fill();
    context.restore();
  }

  // Kreslení hráčů
  for (let id in players) {
    const player = players[id];
    const screenX = player.x - cameraX;
    const screenY = player.y - cameraY;

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

  // Kreslení šipek ukazujících na mimoobrazovkové hráče
  for (let id in players) {
    if (id !== myId) {
      const player = players[id];
      const screenX = player.x - cameraX;
      const screenY = player.y - cameraY;

      if (screenX < 0 || screenX > canvas.width || screenY < 0 || screenY > canvas.height) {
        // Hráč je mimo obrazovku
        const angleToPlayer = Math.atan2(screenY - canvas.height / 2, screenX - canvas.width / 2);
        const arrowX = canvas.width / 2 + Math.cos(angleToPlayer) * (canvas.width / 2 - 20);
        const arrowY = canvas.height / 2 + Math.sin(angleToPlayer) * (canvas.height / 2 - 20);

        context.save();
        context.globalAlpha = 0.5; // Nastavení průhlednosti na 50%
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

  // Zobrazení skóre
  displayScores();

  requestAnimationFrame(gameLoop);
}

// Funkce pro zobrazení skóre
function displayScores() {
  const scoreBoard = document.getElementById('scoreBoard');
  scoreBoard.innerHTML = '<h2>Skóre</h2>';
  for (let id in players) {
    const player = players[id];
    const playerName = id === myId ? 'Ty' : `Hráč ${id.substring(0, 4)}`;
    const scoreEntry = document.createElement('div');
    scoreEntry.textContent = `${playerName}: ${player.score}`;
    scoreEntry.style.color = player.color || 'white';
    scoreBoard.appendChild(scoreEntry);
  }
}

gameLoop();
