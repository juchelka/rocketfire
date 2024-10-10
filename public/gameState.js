// public/gameState.js
export let players = {};
export let projectiles = [];
export let monsters = [];
export let stars = [];
export let myId = null;
export let ping = 0;
export let cycleCount = 0;
export let timeOffset = 0;

export function updatePlayers(serverPlayers) {
    players = JSON.parse(JSON.stringify(serverPlayers));
}

export function updateProjectiles(serverProjectiles) {
    projectiles = serverProjectiles;
}

export function updateStars(serverStars) {
    stars = serverStars;
}

export function updateMonsters(serverMonsters) {
    monsters = serverMonsters;
}

export function updateDiagnostics(data) {
    cycleCount = data.cycleCount;
}

export function updateTimeOffset(serverTime, t0, t2) {
    const RTT = t2 - t0;
    const estimatedServerTime = serverTime + RTT / 2;
    timeOffset = estimatedServerTime - t2;
    ping = RTT / 2;
}

export function getServerTime() {
    return performance.now() + timeOffset;
}