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

Máme hru v Javascriptu. Funguje výborně.

Ve hře střílejí i monstra, ale střely monster lítají moc rychle. Potřebujeme aby střely monster byly žluté a lítaly poloviční rychlostí.
Hvězdy změň ze žlutých na světle šedé.
Zásahy hráče zobrazuj scoreBoard, ostraň tuto informaci z canvasu.

Vyvtoř PHP script ve stylu update.php, který provede úpravy tím, že nahradí části potřebných souborů. 
Funkce updateFile zkontroluje pomocí čtvrtého argumentu count, jestli opravdu došlo k nahrazení.
Dej si pozor na správné escapování a použíj <<<'EOD' pro části kódu.
