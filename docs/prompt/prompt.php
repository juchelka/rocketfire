<?php
require_once __DIR__ . "/PrompterHelper.php";
(new PrompterHelper(
    root: __DIR__ . '/../../',
    ignoreDirs: ["docs",
    ".git",
    ".vscode",
    ".DS_Store",
    "build",
    "node_modules",
    ]
))->dumpWorkspace();
?>

Máme takovou hru v Javascriptu, ale když Když mačkám šipky, nic se neděje.