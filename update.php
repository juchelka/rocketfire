<?php

// Funkce pro aktualizaci souboru s kontrolou počtu nahrazení
function updateFile($filename, $search, $replace, $expectedCount = 1) {
    $content = file_get_contents($filename);
    $newContent = str_replace($search, $replace, $content, $count);
    if ($count !== $expectedCount) {
      echo "\nZměna: \n---\n$search\n-- na --\n$replace\n---\nv souboru $filename neproběhla. Nalezeno $count, namísto $expectedCount výskytů.\n";
      return false;
    } else {
      file_put_contents($filename, $newContent);
      echo "Aktualizován soubor $filename ($count nahrazení)\n";  
      return true;
    }
}

$allDone = true;

// Úprava server/server.js pro náhodné intervaly střelby monster
$serverSearch1 = <<<'EOD'
// Střelba monsters
    if (currentTime - monster.lastShot > 2000) { // Střílí každé 2 sekundy
      const closestPlayer = findClosestPlayer(monster);
      if (closestPlayer) {
        const angle = Math.atan2(closestPlayer.y - monster.y, closestPlayer.x - monster.x);
        shootProjectile(monster.x, monster.y, angle, 'monster');
        monster.lastShot = currentTime;
      }
    }
EOD;

$serverReplace1 = <<<'EOD'
// Střelba monsters
    if (currentTime - monster.lastShot > monster.shootInterval) {
      const closestPlayer = findClosestPlayer(monster);
      if (closestPlayer) {
        const angle = Math.atan2(closestPlayer.y - monster.y, closestPlayer.x - monster.x);
        shootProjectile(monster.x, monster.y, angle, 'monster');
        monster.lastShot = currentTime;
        monster.shootInterval = Math.floor(Math.random() * 5000) + 3000; // Náhodný interval 3-8 sekund
      }
    }
EOD;

$allDone &= updateFile('server/server.js', $serverSearch1, $serverReplace1);

// Úprava server/server.js pro přidání zdraví monster a jména hráče
$serverSearch2 = <<<'EOD'
function createMonster() {
  return {
    x: Math.random() * 2000 - 1000,
    y: Math.random() * 2000 - 1000,
    health: 3,
    lastShot: 0, // Čas poslední střely
  };
}
EOD;

$serverReplace2 = <<<'EOD'
function createMonster() {
  return {
    x: Math.random() * 2000 - 1000,
    y: Math.random() * 2000 - 1000,
    health: 3,
    maxHealth: 3,
    lastShot: 0, // Čas poslední střely
    shootInterval: Math.floor(Math.random() * 5000) + 3000, // Náhodný interval 3-8 sekund
  };
}
EOD;

$allDone &= updateFile('server/server.js', $serverSearch2, $serverReplace2);

// Úprava server/server.js pro aktualizaci skóre
$serverSearch3 = <<<'EOD'
socket.on('connection', (socket) => {
  console.log(`Nový hráč se připojil: ${socket.id}`);

  // Přidání nového hráče do hry
  players[socket.id] = createPlayer(socket.id);
EOD;

$serverReplace3 = <<<'EOD'
socket.on('connection', (socket) => {
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
EOD;

$allDone &= updateFile('server/server.js', $serverSearch3, $serverReplace3);

// Úprava server/server.js pro aktualizaci skóre při zásahu
$serverSearch4 = <<<'EOD'
if (monster.health <= 0) {
          monsters.splice(j, 1);
          // Zvýšení skóre hráče, který příšeru zasáhl
          if (players[proj.ownerId]) {
            players[proj.ownerId].score += 1;
          }
          // Spawn nové příšery
          spawnNewMonster();
        }
EOD;

$serverReplace4 = <<<'EOD'
if (monster.health <= 0) {
          monsters.splice(j, 1);
          // Zvýšení skóre hráče, který příšeru zasáhl
          if (players[proj.ownerId]) {
            players[proj.ownerId].score += 1;
            players[proj.ownerId].monsterHits = (players[proj.ownerId].monsterHits || 0) + 1;
          }
          // Spawn nové příšery
          spawnNewMonster();
        }
EOD;

$allDone &= updateFile('server/server.js', $serverSearch4, $serverReplace4);

// Úprava server/server.js pro aktualizaci skóre při zásahu hráče
$serverSearch5 = <<<'EOD'
if (distance < 20) {
          // Zásah hráče
          player.score = Math.max(0, player.score - 1); // Snížení skóre hráče (minimum 0)
          player.hits = (player.hits || 0) + 1; // Přidání počtu zásahů
          projectiles.splice(i, 1);
          break;
        }
EOD;

$serverReplace5 = <<<'EOD'
if (distance < 20) {
          // Zásah hráče
          player.hits = (player.hits || 0) + 1; // Přidání počtu zásahů
          player.score = (player.monsterHits || 0) - player.hits; // Aktualizace skóre
          projectiles.splice(i, 1);
          break;
        }
EOD;

$allDone &= updateFile('server/server.js', $serverSearch5, $serverReplace5);

// Úprava public/client.js pro přidání ovládání WASD
$clientSearch1 = <<<'EOD'
document.addEventListener('keydown', (event) => {
  if (event.code === 'ArrowUp') keys.up = true;
  if (event.code === 'ArrowLeft') keys.left = true;
  if (event.code === 'ArrowRight') keys.right = true;
  if (event.code === 'ArrowDown') keys.down = true;
  if (event.code === 'Space' && keys.space !== true) {
    keys.space = true;
    socket.emit('shoot', true); // Zahájit střelbu
  }
});

document.addEventListener('keyup', (event) => {
  if (event.code === 'ArrowUp') keys.up = false;
  if (event.code === 'ArrowLeft') keys.left = false;
  if (event.code === 'ArrowRight') keys.right = false;
  if (event.code === 'ArrowDown') keys.down = false;
  if (event.code === 'Space') {
    keys.space = false;
    socket.emit('shoot', false); // Zastavit střelbu
  }
});
EOD;

$clientReplace1 = <<<'EOD'
document.addEventListener('keydown', (event) => {
  if (event.code === 'ArrowUp' || event.code === 'KeyW') keys.up = true;
  if (event.code === 'ArrowLeft' || event.code === 'KeyA') keys.left = true;
  if (event.code === 'ArrowRight' || event.code === 'KeyD') keys.right = true;
  if (event.code === 'ArrowDown' || event.code === 'KeyS') keys.down = true;
  if (event.code === 'Space' && keys.space !== true) {
    keys.space = true;
    socket.emit('shoot', true); // Zahájit střelbu
  }
});

document.addEventListener('keyup', (event) => {
  if (event.code === 'ArrowUp' || event.code === 'KeyW') keys.up = false;
  if (event.code === 'ArrowLeft' || event.code === 'KeyA') keys.left = false;
  if (event.code === 'ArrowRight' || event.code === 'KeyD') keys.right = false;
  if (event.code === 'ArrowDown' || event.code === 'KeyS') keys.down = false;
  if (event.code === 'Space') {
    keys.space = false;
    socket.emit('shoot', false); // Zastavit střelbu
  }
});
EOD;

$allDone &= updateFile('public/client.js', $clientSearch1, $clientReplace1);

// Úprava public/client.js pro přidání health baru monster
$clientSearch2 = <<<'EOD'
// Kreslení příšer
for (let monster of monsters) {
  context.fillStyle = 'green';
  context.beginPath();
  context.arc(monster.x - cameraX, monster.y - cameraY, 20, 0, Math.PI * 2);
  context.fill();
}
EOD;

$clientReplace2 = <<<'EOD'
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
EOD;

$allDone &= updateFile('public/client.js', $clientSearch2, $clientReplace2);

// Úprava public/client.js pro přidání jména hráče a aktualizaci score
$clientSearch3 = <<<'EOD'
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
    const playerName = id === myId ? 'Ty' : `Hráč ${id.substring(0, 4)}`;
    const scoreEntry = document.createElement('div');
    scoreEntry.textContent = `${playerName}: ${player.score} (Zásahy: ${player.hits || 0})`;
    scoreEntry.style.color = player.color || 'white';
    scoreBoard.appendChild(scoreEntry);
  }
}
EOD;

$clientReplace3 = <<<'EOD'
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
EOD;

$allDone &= updateFile('public/client.js', $clientSearch3, $clientReplace3);

// Úprava public/index.html pro přidání tlačítka pro změnu jména
$htmlSearch = <<<'EOD'
<body>
  <canvas id="gameCanvas"></canvas>
  <div id="scoreBoard"></div>
  <script src="/socket.io/socket.io.js"></script>
  <script src="client.js"></script>
</body>
EOD;

$htmlReplace = <<<'EOD'
<body>
  <canvas id="gameCanvas"></canvas>
  <div id="scoreBoard"></div>
  <button id="changeName" style="position: absolute; top: 10px; right: 10px;">Změnit jméno</button>
  <script src="/socket.io/socket.io.js"></script>
  <script src="client.js"></script>
  <script>
    document.getElementById('changeName').addEventListener('click', function() {
      const newName = prompt('Zadejte nové jméno:');
      if (newName) {
        localStorage.setItem('playerName', newName);
        socket.emit('setName', newName);
      }
    });
  </script>
</body>
EOD;

$allDone &= updateFile('public/index.html', $htmlSearch, $htmlReplace);

if ($allDone) {
  echo "\n OK. Včechny změny provedeny.";
} else {
  echo "\n Chyba, některé změny se nepodařilo provést.";
}