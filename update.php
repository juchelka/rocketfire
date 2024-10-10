<?php
function updateFile($filename, $search, $replace, $expectedCount = 1) {
    $content = file_get_contents($filename);
    $newContent = str_replace($search, $replace, $content, $count);
    if ($count !== $expectedCount) {
      echo "\nReplacement in file $filename \n-------\n$search\n--- by ---\n$replace\n-------\nfailed. Found $count occurrences instead of $expectedCount.\n";
      return false;
    } else {
      file_put_contents($filename, $newContent);
      echo "Updated file $filename ($count replacements)\n";  
      return true;
    }
}

$allDone = true;

// Update public/client.js
$clientSearch = <<<'EOD'
// Vstup hráče
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

$clientReplace = <<<'EOD'
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
EOD;

$allDone &= updateFile('public/client.js', $clientSearch, $clientReplace);

// Update server/server.js
$serverSearch = <<<'EOD'
socket.on('movement', (data) => {
  const player = players[socket.id];
  if (player) {
    if (data.left) {
      player.angle -= 0.05;
    }
    if (data.right) {
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
  }
});
EOD;

$serverReplace = <<<'EOD'
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
      player.x += Math.cos(player.angle - Math.PI / 2) * 5;
      player.y += Math.sin(player.angle - Math.PI / 2) * 5;
    }
    if (data.right) {
      player.x += Math.cos(player.angle + Math.PI / 2) * 5;
      player.y += Math.sin(player.angle + Math.PI / 2) * 5;
    }
  }
});
EOD;

$allDone &= updateFile('server/server.js', $serverSearch, $serverReplace);

if ($allDone) {
  echo "\nOK. All changes have been made.\n";
} else {
  echo "\nError: Some changes could not be made.\n";
}

// Suggested commit message:
// "Change keys A and D to sidestep left and right instead of rotating the spaceship"