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

Máme takovou hru v Javascriptu. Funguje výborně.

Ve hře střílejí i monstra, ale střely monster lítají moc rychle. Potřebujeme aby střely monster byly žluté a lítaly poloviční rychlostí.
Zároveň by se u hráče měl započítávat počet zásahů monstrem.

Vyvtoř PHP script, který provede úpravy tím, že nahradí části potřebných souborů. 
Dej si pozor na správné escapování a použíj <<<'EOD' pro části kódu.
