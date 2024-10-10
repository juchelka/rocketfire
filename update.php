<?php

// Funkce pro aktualizaci souboru
function updateFile($filename, $search, $replace) {
    $content = file_get_contents($filename);
    $content = str_replace($search, $replace, $content);
    file_put_contents($filename, $content);
    echo "Updated $filename\n";
}

// Aktualizace server/server.js
$serverSearch = <<<'EOD'
// Funkce pro vytvoření nového projektilu
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
// Funkce pro vytvoření nového projektilu
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

// Další aktualizace server/server.js pro počítání zásahů
$serverSearch2 = <<<'EOD'
    if (proj.ownerId === 'monster') {
      for (let playerId in players) {
        const player = players[playerId];
        const dx = proj.x - player.x;
        const dy = proj.y - player.y;
        const distance = Math.sqrt(dx * dx + dy * dy);
        if (distance < 20) {
          // Zásah hráče
          player.score = Math.max(0, player.score - 1); // Snížení skóre hráče (minimum 0)
          projectiles.splice(i, 1);
          break;
        }
      }
    }
EOD;

$serverReplace2 = <<<'EOD'
    if (proj.ownerId === 'monster') {
      for (let playerId in players) {
        const player = players[playerId];
        const dx = proj.x - player.x;
        const dy = proj.y - player.y;
        const distance = Math.sqrt(dx * dx + dy * dy);
        if (distance < 20) {
          // Zásah hráče
          player.score = Math.max(0, player.score - 1); // Snížení skóre hráče (minimum 0)
          player.hits = (player.hits || 0) + 1; // Přidání počtu zásahů
          projectiles.splice(i, 1);
          break;
        }
      }
    }
EOD;

updateFile('server/server.js', $serverSearch2, $serverReplace2);

// Aktualizace server/server.js pro pohyb projektilů
$serverSearch3 = <<<'EOD'
  // Pohyb projektilů
  for (let i = projectiles.length - 1; i >= 0; i--) {
    const proj = projectiles[i];
    proj.x += Math.cos(proj.angle) * 20;
    proj.y += Math.sin(proj.angle) * 20;
EOD;

$serverReplace3 = <<<'EOD'
  // Pohyb projektilů
  for (let i = projectiles.length - 1; i >= 0; i--) {
    const proj = projectiles[i];
    proj.x += Math.cos(proj.angle) * proj.speed;
    proj.y += Math.sin(proj.angle) * proj.speed;
EOD;

updateFile('server/server.js', $serverSearch3, $serverReplace3);

// Aktualizace public/client.js
$clientSearch = <<<'EOD'
    context.fillStyle = proj.ownerId === 'monster' ? 'purple' : 'red'; // Změna barvy pro střely příšer
EOD;

$clientReplace = <<<'EOD'
    context.fillStyle = proj.ownerId === 'monster' ? 'yellow' : 'red'; // Změna barvy pro střely příšer na žlutou
EOD;

updateFile('public/client.js', $clientSearch, $clientReplace);

// Aktualizace public/client.js pro zobrazení počtu zásahů
$clientSearch2 = <<<'EOD'
  // Zobrazení diagnostických dat
  context.fillStyle = 'white';
  context.font = '16px Arial';
  context.fillText(`Ping: ${ping.toFixed(2)} ms`, 10, 20);
  context.fillText(`Server: ${cycleCount}/s of 60`, 10, 40);
EOD;

$clientReplace2 = <<<'EOD'
  // Zobrazení diagnostických dat
  context.fillStyle = 'white';
  context.font = '16px Arial';
  context.fillText(`Ping: ${ping.toFixed(2)} ms`, 10, 20);
  context.fillText(`Server: ${cycleCount}/s of 60`, 10, 40);
  if (myId && players[myId]) {
    context.fillText(`Zásahy: ${players[myId].hits || 0}`, 10, 60);
  }
EOD;

updateFile('public/client.js', $clientSearch2, $clientReplace2);

echo "Úpravy byly úspěšně provedeny.\n";