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

// Update public/gameState.js
$gameStateSearch = <<<'EOD'
export let myId = null;
EOD;

$gameStateReplace = <<<'EOD'
let myId = null;
export function setMyId(id) {
    myId = id;
}
export function getMyId() {
    return myId;
}
EOD;

$allDone &= updateFile('public/gameState.js', $gameStateSearch, $gameStateReplace);

// Update public/client.js
$clientSearch = "socket.on('yourId', (id) => gameState.myId = id);";
$clientReplace = "socket.on('yourId', (id) => gameState.setMyId(id));";

$allDone &= updateFile('public/client.js', $clientSearch, $clientReplace);

// Update public/render.js
$renderSearch = <<<'EOD'
import { players, projectiles, monsters, stars, myId, ping, cycleCount } from './gameState.js';

export function gameLoop(canvas, context) {
EOD;

$renderReplace = <<<'EOD'
import { players, projectiles, monsters, stars, getMyId, ping, cycleCount } from './gameState.js';

let canvasWidth, canvasHeight;

export function gameLoop(canvas, context) {
    canvasWidth = canvas.width;
    canvasHeight = canvas.height;
EOD;

$allDone &= updateFile('public/render.js', $renderSearch, $renderReplace);

// Update getCameraPosition function in public/render.js
$cameraSearch = <<<'EOD'
function getCameraPosition() {
    let cameraX = 0;
    let cameraY = 0;
    if (myId && players[myId]) {
        cameraX = players[myId].x - canvas.width / 2;
        cameraY = players[myId].y - canvas.height / 2;
    }
    return { x: cameraX, y: cameraY };
}
EOD;

$cameraReplace = <<<'EOD'
function getCameraPosition() {
    let cameraX = 0;
    let cameraY = 0;
    const myId = getMyId();
    if (myId && players[myId]) {
        cameraX = players[myId].x - canvasWidth / 2;
        cameraY = players[myId].y - canvasHeight / 2;
    }
    return { x: cameraX, y: cameraY };
}
EOD;

$allDone &= updateFile('public/render.js', $cameraSearch, $cameraReplace);

// Update drawPlayers function in public/render.js
$drawPlayersSearch = <<<'EOD'
function drawPlayers(context, camera) {
    for (let id in players) {
        const player = players[id];
        const screenX = player.x - camera.x;
        const screenY = player.y - camera.y;

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
}
EOD;

$drawPlayersReplace = <<<'EOD'
function drawPlayers(context, camera) {
    for (let id in players) {
        const player = players[id];
        const screenX = player.x - camera.x;
        const screenY = player.y - camera.y;

        if (screenX + 20 < 0 || screenX - 20 > canvasWidth || screenY + 20 < 0 || screenY - 20 > canvasHeight) {
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
}
EOD;

$allDone &= updateFile('public/render.js', $drawPlayersSearch, $drawPlayersReplace);

// Update drawOffscreenPlayerArrows function in public/render.js
$arrowsSearch = <<<'EOD'
function drawOffscreenPlayerArrows(canvas, context, camera) {
    for (let id in players) {
        if (id !== myId) {
EOD;

$arrowsReplace = <<<'EOD'
function drawOffscreenPlayerArrows(canvas, context, camera) {
    const myId = getMyId();
    for (let id in players) {
        if (id !== myId) {
EOD;

$allDone &= updateFile('public/render.js', $arrowsSearch, $arrowsReplace);

if ($allDone) {
    echo "\nOK. All changes have been made.\n";
} else {
    fwrite(STDERR, "\nError: Some changes could not be made.\n");
}

// Suggested commit message:
// "Fix read-only property assignment and canvas reference issues"
?>