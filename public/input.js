// public/input.js
export const keys = {};

export function setupInputListeners(socket) {
    document.addEventListener('keydown', (event) => {
        if (event.code === 'ArrowUp' || event.code === 'KeyW') keys.up = true;
        if (event.code === 'KeyA') keys.left = true;
        if (event.code === 'KeyD') keys.right = true;
        if (event.code === 'ArrowLeft') keys.rotateLeft = true;
        if (event.code === 'ArrowRight') keys.rotateRight = true;
        if (event.code === 'ArrowDown' || event.code === 'KeyS') keys.down = true;
        if (event.code === 'Space' && keys.space !== true) {
            keys.space = true;
            socket.emit('shoot', true);
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
            socket.emit('shoot', false);
        }
    });
}

export function sendInput(socket) {
    socket.emit('movement', keys);
    requestAnimationFrame(() => sendInput(socket));
}