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

V prohlížeči jsem dostal chybu:

Failed to load resource: the server responded with a status of 404 (Not Found)Understand this error
render.js:136 Uncaught ReferenceError: myId is not defined
    at displayScores (render.js:136:51)
    at gameLoop (render.js:17:5)
    at render.js:20:33Understand this error