// server/gameState.js
let players = {};
let projectiles = [];
let monsters = [];
let stars = [];

function createPlayer(id) {
    return {
        id: id,
        x: Math.random() * 800,
        y: Math.random() * 600,
        angle: 0,
        speed: 0,
        color: getRandomPastelColor(),
        score: 0,
    };
}

function createMonster() {
    return {
        x: Math.random() * 2000 - 1000,
        y: Math.random() * 2000 - 1000,
        health: 3,
        maxHealth: 3,
        lastShot: 0,
        shootInterval: Math.floor(Math.random() * 5000) + 3000,
    };
}

function createProjectile(x, y, angle, ownerId) {
    return {
        x: x,
        y: y,
        startX: x,
        startY: y,
        angle: angle,
        ownerId: ownerId,
        speed: ownerId === 'monster' ? 5 : 20,
    };
}

function getRandomPastelColor() {
    const r = Math.floor((Math.random() * 127) + 127);
    const g = Math.floor((Math.random() * 127) + 127);
    const b = Math.floor((Math.random() * 127) + 127);
    return `rgb(${r},${g},${b})`;
}

module.exports = {
    players,
    projectiles,
    monsters,
    stars,
    createPlayer,
    createMonster,
    createProjectile,
    getRandomPastelColor
};