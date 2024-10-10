// public/render.js
import { players, projectiles, monsters, stars, myId, ping, cycleCount } from './gameState.js';

export function gameLoop(canvas, context) {
    context.clearRect(0, 0, canvas.width, canvas.height);

    const camera = getCameraPosition();
    drawStars(context, camera);
    drawProjectiles(context, camera);
    drawMonsters(context, camera);
    drawPlayers(context, camera);
    drawOffscreenPlayerArrows(canvas, context, camera);
    displayScores();
    displayDiagnostics(context);

    requestAnimationFrame(() => gameLoop(canvas, context));
}

function getCameraPosition() {
    let cameraX = 0;
    let cameraY = 0;
    if (myId && players[myId]) {
        cameraX = players[myId].x - canvas.width / 2;
        cameraY = players[myId].y - canvas.height / 2;
    }
    return { x: cameraX, y: cameraY };
}

function drawStars(context, camera) {
    for (let star of stars) {
        context.fillStyle = 'lightgray';
        context.beginPath();
        context.arc(star.x - camera.x, star.y - camera.y, star.size, 0, Math.PI * 2);
        context.fill();
    }
}

function drawProjectiles(context, camera) {
    context.save();
    for (let proj of projectiles) {
        context.translate(proj.x - camera.x, proj.y - camera.y);
        context.rotate(proj.angle);
        context.fillStyle = proj.ownerId === 'monster' ? 'yellow' : 'red';
        context.beginPath();
        context.moveTo(5, 0);
        context.lineTo(-5, -3);
        context.lineTo(-5, 3);
        context.closePath();
        context.fill();
        context.setTransform(1, 0, 0, 1, 0, 0);
    }
    context.restore();
}

function drawMonsters(context, camera) {
    for (let monster of monsters) {
        context.fillStyle = 'green';
        context.beginPath();
        context.arc(monster.x - camera.x, monster.y - camera.y, 20, 0, Math.PI * 2);
        context.fill();

        const barWidth = 40;
        const barHeight = 5;
        const healthPercentage = monster.health / monster.maxHealth;
        context.fillStyle = 'red';
        context.fillRect(monster.x - camera.x - barWidth / 2, monster.y - camera.y - 30, barWidth, barHeight);
        context.fillStyle = 'green';
        context.fillRect(monster.x - camera.x - barWidth / 2, monster.y - camera.y - 30, barWidth * healthPercentage, barHeight);
    }
}

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

function drawOffscreenPlayerArrows(canvas, context, camera) {
    for (let id in players) {
        if (id !== myId) {
            const player = players[id];
            const screenX = player.x - camera.x;
            const screenY = player.y - camera.y;

            if (screenX < 0 || screenX > canvas.width || screenY < 0 || screenY > canvas.height) {
                const angleToPlayer = Math.atan2(screenY - canvas.height / 2, screenX - canvas.width / 2);
                const arrowX = canvas.width / 2 + Math.cos(angleToPlayer) * (canvas.width / 2 - 20);
                const arrowY = canvas.height / 2 + Math.sin(angleToPlayer) * (canvas.height / 2 - 20);

                context.save();
                context.globalAlpha = 0.5;
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
}

function displayScores() {
    const scoreBoard = document.getElementById('scoreBoard');
    scoreBoard.innerHTML = '<h2>Skóre</h2>';
    for (let id in players) {
        const player = players[id];
        const playerName = player.name || (id === myId ? 'Ty' : `Hráč ${id.substring(0, 4)}`);
        const scoreEntry = document.createElement('div');
        scoreEntry.textContent = `${playerName}: ${player.score} (Zásahy M: ${player.monsterHits || 0}, Zásahy H: ${player.hits || 0})`;
        scoreEntry.style.color = player.color || 'white';
        scoreBoard.appendChild(scoreEntry);
    }
}

function displayDiagnostics(context) {
    context.fillStyle = 'white';
    context.font = '16px Arial';
    context.fillText(`Ping: ${ping.toFixed(2)} ms`, 10, 20);
    context.fillText(`Server: ${cycleCount}/s of 60`, 10, 40);
}