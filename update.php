<?php

// Funkce pro aktualizaci souboru s kontrolou počtu nahrazení
function updateFile($filename, $search, $replace, $expectedCount = 1) {
    $content = file_get_contents($filename);
    $newContent = str_replace($search, $replace, $content, $count);
    if ($count !== $expectedCount) {
        echo "Varování: V souboru $filename bylo provedeno $count nahrazení místo očekávaných $expectedCount.\n";
    }
    file_put_contents($filename, $newContent);
    echo "Aktualizován soubor $filename ($count nahrazení)\n";
}

// Úprava server/server.js pro poloviční rychlost střel monster
$serverSearch = <<<'EOD'
function createProjectile(x, y, angle, ownerId) {
  return {
    x: x,
    y: y,
    startX: x,
    startY: y,
    angle: angle,
    ownerId: ownerId,
  };
}
EOD;

$serverReplace = <<<'EOD'
function createProjectile(x, y, angle, ownerId) {
  return {
    x: x,
    y: y,
    startX: x,
    startY: y,
    angle: angle,
    ownerId: ownerId,
    speed: ownerId === 'monster' ? 10 : 20, // Poloviční rychlost pro střely monster
  };
}
EOD;

updateFile('server/server.js', $serverSearch, $serverReplace);

// Úprava server/server.js pro použití rychlosti projektilů
$serverSearch2 = <<<'EOD'
  // Pohyb projektilů
  for (let i = projectiles.length - 1; i >= 0; i--) {
    const proj = projectiles[i];
    proj.x += Math.cos(proj.angle) * 20;
    proj.y += Math.sin(proj.angle) * 20;
EOD;

$serverReplace2 = <<<'EOD'
  // Pohyb projektilů
  for (let i = projectiles.length - 1; i >= 0; i--) {
    const proj = projectiles[i];
    proj.x += Math.cos(proj.angle) * proj.speed;
    proj.y += Math.sin(proj.angle) * proj.speed;
EOD;

updateFile('server/server.js', $serverSearch2, $serverReplace2);

// Úprava public/client.js pro změnu barvy střel monster na žlutou
$clientSearch = <<<'EOD'
    context.fillStyle = proj.ownerId === 'monster' ? 'purple' : 'red'; // Změna barvy pro střely příšer
EOD;

$clientReplace = <<<'EOD'
    context.fillStyle = proj.ownerId === 'monster' ? 'yellow' : 'red'; // Změna barvy pro střely příšer na žlutou
EOD;

updateFile('public/client.js', $clientSearch, $clientReplace);

// Úprava public/client.js pro změnu barvy hvězd na světle šedou
$clientSearch2 = <<<'EOD'
  // Kreslení hvězd
  for (let star of stars) {
    context.fillStyle = 'yellow';
    context.beginPath();
EOD;

$clientReplace2 = <<<'EOD'
  // Kreslení hvězd
  for (let star of stars) {
    context.fillStyle = 'lightgray';
    context.beginPath();
EOD;

updateFile('public/client.js', $clientSearch2, $clientReplace2);

// Úprava public/client.js pro odstranění zobrazení zásahů z canvasu
$clientSearch3 = <<<'EOD'
  context.fillStyle = 'white';
  context.font = '16px Arial';
  context.fillText(`Ping: ${ping.toFixed(2)} ms`, 10, 20);
  context.fillText(`Server: ${cycleCount}/s of 60`, 10, 40);
  if (myId && players[myId]) {
    context.fillText(`Zásahy: ${players[myId].hits || 0}`, 10, 60);
  }
EOD;

$clientReplace3 = <<<'EOD'
  context.fillStyle = 'white';
  context.font = '16px Arial';
  context.fillText(`Ping: ${ping.toFixed(2)} ms`, 10, 20);
  context.fillText(`Server: ${cycleCount}/s of 60`, 10, 40);
EOD;

updateFile('public/client.js', $clientSearch3, $clientReplace3);

// Úprava public/client.js pro přidání zobrazení zásahů do scoreBoard
$clientSearch4 = <<<'EOD'
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
    scoreEntry.textContent = `${playerName}: ${player.score}`;
    scoreEntry.style.color = player.color || 'white';
    scoreBoard.appendChild(scoreEntry);
  }
}
EOD;

$clientReplace4 = <<<'EOD'
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

updateFile('public/client.js', $clientSearch4, $clientReplace4);
