Toto je aktuální kód:

<?php
require_once __DIR__ . "/PrompterHelper.php";
(new PrompterHelper(
    root: __DIR__ . '/../../',
    ignorePaths: ["docs",
    ".git",
    ".gitingnore",
    ".vscode",
    ".DS_Store",
    "build",
    "node_modules",
    "package-lock.json",
    ]
))->dumpWorkspace();
?>

Po vystřelení jsem dostal chybu:

/Users/vjuchelka/Working/rocketfire/server/server.js:99
            projectiles.push(createProjectile(player.x, player.y, player.angle, socket.id));
                        ^

ReferenceError: createProjectile is not defined
    at Socket.<anonymous> (/Users/vjuchelka/Working/rocketfire/server/server.js:99:25)
    at Socket.emit (node:events:519:28)
    at Socket.emitUntyped (/Users/vjuchelka/Working/rocketfire/node_modules/socket.io/dist/typed-events.js:69:22)
    at /Users/vjuchelka/Working/rocketfire/node_modules/socket.io/dist/socket.js:697:39
    at process.processTicksAndRejections (node:internal/process/task_queues:85:11)

Node.js v22.9.0