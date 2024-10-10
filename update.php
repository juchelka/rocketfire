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

$allDone = true;

// Update index.html
$indexHtmlSearch = '<script src="client.js"></script>';
$indexHtmlReplace = '<script src="client.js" type="module"></script>';

$allDone &= updateFile('public/index.html', $indexHtmlSearch, $indexHtmlReplace);

// Update client.js
$clientJsSearch = <<<'EOD'
// public/client.js
import { setupInputListeners, sendInput } from './input.js';
import { gameLoop } from './render.js';
import * as gameState from './gameState.js';

const socket = io();
EOD;

$clientJsReplace = <<<'EOD'
// public/client.js
import { setupInputListeners, sendInput } from './input.js';
import { gameLoop } from './render.js';
import * as gameState from './gameState.js';

const socket = io();

// Expose socket to window for the change name button
window.socket = socket;
EOD;

$allDone &= updateFile('public/client.js', $clientJsSearch, $clientJsReplace);

// Update the change name button event listener in index.html
$changeNameSearch = <<<'EOD'
<script>
    document.getElementById('changeName').addEventListener('click', function() {
      const newName = prompt('Zadejte nové jméno:');
      if (newName) {
        localStorage.setItem('playerName', newName);
        socket.emit('setName', newName);
      }
    });
  </script>
EOD;

$changeNameReplace = <<<'EOD'
<script>
    document.getElementById('changeName').addEventListener('click', function() {
      const newName = prompt('Zadejte nové jméno:');
      if (newName) {
        localStorage.setItem('playerName', newName);
        window.socket.emit('setName', newName);
      }
    });
  </script>
EOD;

$allDone &= updateFile('public/index.html', $changeNameSearch, $changeNameReplace);

if ($allDone) {
    echo "\nOK. All changes have been made.\n";
} else {
    fwrite(STDERR, "\nError: Some changes could not be made.\n");
}

// Suggested commit message:
// "Fix module import issues and update client-side code to work as ES6 module"
?>