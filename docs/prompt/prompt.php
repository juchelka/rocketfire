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

Chtěli bychom aby herní canvas zabíral celou výšku viewportu a 80% šířky.

Naváděcí šipka, která ukazuje pozici soupeře, který se nevleze na canvas by měla mít alfu 50%.

Omezení vzdálenosti střely je implementováno špatně, takto funguje střílení jen v určité oblasti.
Mělo by to být udělané tak, že střela bude mít definovaný dostřel třeba 1000 a při vystřelení si zapamatuje souřadnice, 
odkud byla vypálena a jakmile její pozice bude vzdálená od místa vypálení víc než 1000 tak střela zanikne.
