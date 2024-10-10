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

Máme hru v Javascriptu. Funguje výborně.

Proveď tyto změny:
Klávesy A a D nemají otáčet raketou, ale mají dělat úkrok doprava a doleva.

Vyvtoř PHP script ve stylu update.php, který provede úpravy tím, že nahradí části potřebných souborů. 
Dej si pozor na správné escapování a použíj <<<'EOD' pro části kódu.
Na konci dej do komentáře navrhnout krátkou commit message v angličtině.
