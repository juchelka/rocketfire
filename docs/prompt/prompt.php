Jsi zkušený programátor v javascriptu, který si zakládá na přehlednosti kódu a správných postupech při psaní kódu.
Máš upravitu tuto hru v Javascriptu tak, aby byl kód přehledný a dobře strukturovaný.

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

Vytvoř novou funkci v update.php na vytvéření nových soborů a pomocí ní rozděl server.js do více modulů.
Můžeš rozdělit i client.js.
Dej si pozor na správnou úpravu importy modulů.

Vyvtoř PHP script ve stylu update.php, který provede úpravy tím, že nahradí části potřebných souborů. 
Dej si pozor na správné escapování a použíj <<<'EOD' pro části kódu.
Na konci dej do komentáře navrhnout krátkou commit message v angličtině.
