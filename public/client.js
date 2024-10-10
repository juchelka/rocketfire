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
let monsters = [];
let ping = 0;
let cycleCount = 0;
let timeOffset = 0;

// Funkce pro změnu velikosti plátna
function resizeCanvas() {
  canvas.width = canvas.clientWidth;
  canvas.height = canvas.clientHeight;
}
window.addEventListener('resize', resizeCanvas);
resizeCanvas();

// Vstup hráče
document.addEventListener('keydown', (event) => {
  if (event.code === 'ArrowUp' || event.code === 'KeyW') keys.up = true;
  if (event.code === 'KeyA') keys.left = true; // Sidestep left
  if (event.code === 'KeyD') keys.right = true; // Sidestep right
  if (event.code === 'ArrowLeft') keys.rotateLeft = true; // Rotate left
  if (event.code === 'ArrowRight') keys.rotateRight = true; // Rotate right
  if (event.code === 'ArrowDown' || event.code === 'KeyS') keys.down = true;
  if (event.code === 'Space' && keys.space !== true) {
    keys.space = true;
    socket.emit('shoot', true); // Zahájit střelbu
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
    socket.emit('shoot', false); // Zastavit střelbu
  }
});

// Odesílání vstupu na server
function sendInput() {
  socket.emit('movement', keys);
  requestAnimationFrame(sendInput);
}
requestAnimationFrame(sendInput);

// Synchronizace času s použitím RTT
function synchronizeTime() {
  const t0 = performance.now();
  socket.emit('syncTime', t0);
}

// Synchronizace každých 5 sekund
setInterval(synchronizeTime, 5000);

// Příjem synchronizovaného času ze serveru
socket.on('syncTimeResponse', (serverTime, t0) => {
  const t2 = performance.now();
  const RTT = t2 - t0;
  const estimatedServerTime = serverTime + RTT / 2;
  timeOffset = estimatedServerTime - t2;
  ping = (t2 - t0) / 2;
  console.log({
    t0,
    t2,
    delta: t2-t0, 
    serverTime,
  });
});

function getServerTime() {
  return performance.now() + timeOffset;
}

// Přijímání aktualizací od serveru
socket.on('updatePlayers', (serverPlayers) => {
  players = JSON.parse(JSON.stringify(serverPlayers));
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

socket.on('updateMonsters', (serverMonsters) => {
  monsters = serverMonsters;
});

socket.on('serverDiagnostics', (data) => {
  cycleCount = data.cycleCount;
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
    context.fillStyle = 'lightgray';
    context.beginPath();
    context.arc(star.x - cameraX, star.y - cameraY, star.size, 0, Math.PI * 2);
    context.fill();
  }

  // Kreslení projektilů
  context.save();
  for (let proj of projectiles) {
    context.translate(proj.x - cameraX, proj.y - cameraY);
    context.rotate(proj.angle);
    context.fillStyle = proj.ownerId === 'monster' ? 'yellow' : 'red'; // Změna barvy pro střely příšer na žlutou
    context.beginPath();
    context.moveTo(5, 0);
    context.lineTo(-5, -3);
    context.lineTo(-5, 3);
    context.closePath();
    context.fill();
    context.setTransform(1, 0, 0, 1, 0, 0); // Reset transform
  }
  context.restore();

  // Kreslení příšer
  for (let monster of monsters) {
    context.fillStyle = 'green';
    context.beginPath();
    context.arc(monster.x - cameraX, monster.y - cameraY, 20, 0, Math.PI * 2);
    context.fill();

    // Kreslení health baru
    const barWidth = 40;
    const barHeight = 5;
    const healthPercentage = monster.health / monster.maxHealth;
    context.fillStyle = 'red';
    context.fillRect(monster.x - cameraX - barWidth / 2, monster.y - cameraY - 30, barWidth, barHeight);
    context.fillStyle = 'green';
    context.fillRect(monster.x - cameraX - barWidth / 2, monster.y - cameraY - 30, barWidth * healthPercentage, barHeight);
  }

  // Kreslení hráčů
  for (let id in players) {
    const player = players[id];
    const screenX = player.x - cameraX;
    const screenY = player.y - cameraY;

    // Culling off-screen players early to improve performance
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

  // Zobrazení diagnostických dat
  context.fillStyle = 'white';
  context.font = '16px Arial';
  context.fillText(`Ping: ${ping.toFixed(2)} ms`, 10, 20);
  context.fillText(`Server: ${cycleCount}/s of 60`, 10, 40);

  requestAnimationFrame(gameLoop);
}

// Funkce pro zobrazení skóre
function displayScores() {
  const scoreBoard = document.getElementById('scoreBoard');
  while (scoreBoard.firstChild) {
    scoreBoard.removeChild(scoreBoard.firstChild);
  }
  const title = document.createElement('h2');
  title.textContent = 'Skóre';
  scoreBoard.appendChild(title);
  for (let id in players) {
    const player = players[id];
    const playerName = player.name || (id === myId ? 'Ty' : `Hráč ${id.substring(0, 4)}`);
    const scoreEntry = document.createElement('div');
    scoreEntry.textContent = `${playerName}: ${player.score} (Zásahy M: ${player.monsterHits || 0}, Zásahy H: ${player.hits || 0})`;
    scoreEntry.style.color = player.color || 'white';
    scoreBoard.appendChild(scoreEntry);
  }
}

// Načtení jména z local storage a odeslání na server
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

gameLoop();