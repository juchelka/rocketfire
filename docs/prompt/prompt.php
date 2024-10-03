<?php
require_once __DIR__ . "/PrompterHelper.php";
(new PrompterHelper(
    root: __DIR__ . '/../../',
    ignorePaths: ["docs",
    ".git",
    ".vscode",
    ".DS_Store",
    "build",
    "node_modules",
    "package-lock.json",
    ]
))->dumpWorkspace();
?>

Máme takovou hru v Javascriptu.

Trojúhleník který znározňuje raketu je otočený o 90 stupňů doleva. Prosím narovnat.
Potřebujeme aby fungovala i šipka zpět.
Na pozadí bychom chtěli kvůli orientace v prostoru náhodně rozmístěné hvězdy, což by byly žlutá kolečka o velikostech 3-5 bodů.
Pozice hvězd bude na serveru a přenese se pouze po připojení. 
Kamera by měla sledovat raketu a svět by tím pádem měl být neohraničený.
