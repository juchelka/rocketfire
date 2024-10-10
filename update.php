<?php

function updateFile($filename, $search, $replace, $expectedCount = 1) {
    $content = file_get_contents($filename);
    $newContent = str_replace($search, $replace, $content, $count);
    if ($count !== $expectedCount) {
        fwrite(STDERR, "\nReplacement in file $filename \n-------\n$search\n--- by ---\n$replace\n-------\nfailed. Found $count occurrences instead of $expectedCount.\n");
        return false;
    } else {
        file_put_contents($filename, $newContent);
        echo "Updated file $filename ($count replacements)\n";  
        return true;
    }
}

$allDone = true;

// Update server/server.js to import createProjectile
$serverImportSearch = "const { players, projectiles, monsters, stars, createPlayer, createMonster } = require('./gameState');";
$serverImportReplace = "const { players, projectiles, monsters, stars, createPlayer, createMonster, createProjectile } = require('./gameState');";

$allDone &= updateFile('server/server.js', $serverImportSearch, $serverImportReplace);

// Update server/gameState.js to export createProjectile
$gameStateExportSearch = <<<'EOD'
module.exports = {
    players,
    projectiles,
    monsters,
    stars,
    createPlayer,
    createMonster,
    createProjectile,
    getRandomPastelColor
};
EOD;

$gameStateExportReplace = <<<'EOD'
module.exports = {
    players,
    projectiles,
    monsters,
    stars,
    createPlayer,
    createMonster,
    createProjectile,
    getRandomPastelColor
};
EOD;

$allDone &= updateFile('server/gameState.js', $gameStateExportSearch, $gameStateExportReplace);

if ($allDone) {
    echo "\nOK. All changes have been made.\n";
} else {
    fwrite(STDERR, "\nError: Some changes could not be made.\n");
}

// Suggested commit message:
// "Fix projectile creation issue by properly importing and exporting createProjectile function"
?>