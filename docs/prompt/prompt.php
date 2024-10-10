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

Proveď tyto změny:
Monstra budou mít nad sebou takový ten klasický bar, označující kolik jim zbývá života.
Monstra budou střílet né všechny najednou, ale v náhodných intervalech od 3 do 8 vteřin.
Umožni pohyb i pomocí kláves W S A D tak, že W a S budou fungovat stejně jako šipky nahoru a dolu a klávesy A a D budou sloučit jako úkrok do boku.
Na scoreBoard se bude počítat i výsledné score = Počet zásahů kdy hráč zasáhne monstrum - počet zásahů kdy monstrum zasáhne hráče.
Bude možné vyplnit jméno, které se zobrazí na scoreBoard. Toto jméno se uloží do local storage.

Vyvtoř PHP script ve stylu update.php, který provede úpravy tím, že nahradí části potřebných souborů. 
Dej si pozor na správné escapování a použíj <<<'EOD' pro části kódu.
