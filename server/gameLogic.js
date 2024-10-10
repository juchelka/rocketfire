// server/gameLogic.js
const { players, projectiles, monsters, createMonster, createProjectile } = require('./gameState');

function updateGameState() {
    updateProjectiles();
    updateMonsters();
}

function updateProjectiles() {
    for (let i = projectiles.length - 1; i >= 0; i--) {
        const proj = projectiles[i];
        proj.x += Math.cos(proj.angle) * proj.speed;
        proj.y += Math.sin(proj.angle) * proj.speed;

        if (handleProjectileCollisions(proj, i)) continue;

        if (isProjectileOutOfRange(proj)) {
            projectiles.splice(i, 1);
        }
    }
}

function handleProjectileCollisions(proj, index) {
    // Check collision with monsters
    for (let j = monsters.length - 1; j >= 0; j--) {
        const monster = monsters[j];
        if (checkCollision(proj, monster, 20) && proj.ownerId !== 'monster') {
            handleMonsterHit(monster, j, proj.ownerId);
            projectiles.splice(index, 1);
            return true;
        }
    }

    // Check collision with players (only for monster projectiles)
    if (proj.ownerId === 'monster') {
        for (let playerId in players) {
            const player = players[playerId];
            if (checkCollision(proj, player, 20)) {
                handlePlayerHit(player);
                projectiles.splice(index, 1);
                return true;
            }
        }
    }

    return false;
}

function checkCollision(obj1, obj2, distance) {
    const dx = obj1.x - obj2.x;
    const dy = obj1.y - obj2.y;
    return Math.sqrt(dx * dx + dy * dy) < distance;
}

function handleMonsterHit(monster, index, playerId) {
    monster.health -= 1;
    if (monster.health <= 0) {
        monsters.splice(index, 1);
        if (players[playerId]) {
            players[playerId].score += 1;
            players[playerId].monsterHits = (players[playerId].monsterHits || 0) + 1;
        }
        monsters.push(createMonster());
    }
}

function handlePlayerHit(player) {
    player.hits = (player.hits || 0) + 1;
    player.score = (player.monsterHits || 0) - player.hits;
}

function isProjectileOutOfRange(proj) {
    const dx = proj.x - proj.startX;
    const dy = proj.y - proj.startY;
    return Math.sqrt(dx * dx + dy * dy) > 1000;
}

function updateMonsters() {
    const currentTime = Date.now();
    for (let monster of monsters) {
        monster.x += (Math.random() - 0.5) * 2;
        monster.y += (Math.random() - 0.5) * 2;

        if (currentTime - monster.lastShot > monster.shootInterval) {
            const closestPlayer = findClosestPlayer(monster);
            if (closestPlayer) {
                const angle = Math.atan2(closestPlayer.y - monster.y, closestPlayer.x - monster.x);
                projectiles.push(createProjectile(monster.x, monster.y, angle, 'monster'));
                monster.lastShot = currentTime;
                monster.shootInterval = Math.floor(Math.random() * 5000) + 3000;
            }
        }
    }
}

function findClosestPlayer(monster) {
    let closestPlayer = null;
    let closestDistance = Infinity;
    for (let playerId in players) {
        const player = players[playerId];
        const distance = Math.sqrt(Math.pow(player.x - monster.x, 2) + Math.pow(player.y - monster.y, 2));
        if (distance < closestDistance) {
            closestDistance = distance;
            closestPlayer = player;
        }
    }
    return closestPlayer;
}

module.exports = { updateGameState };