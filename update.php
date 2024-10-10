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

// Update render.js to use getMyId() instead of myId
$renderSearch = 'const playerName = player.name || (id === myId ? \'Ty\' : `Hráč ${id.substring(0, 4)}`);';
$renderReplace = 'const playerName = player.name || (id === getMyId() ? \'Ty\' : `Hráč ${id.substring(0, 4)}`);';

$allDone &= updateFile('public/render.js', $renderSearch, $renderReplace);

// Check if all necessary files are present in the public directory
$requiredFiles = ['client.js', 'index.html', 'gameState.js', 'render.js', 'input.js'];
foreach ($requiredFiles as $file) {
    if (!file_exists("public/$file")) {
        fwrite(STDERR, "\nWarning: File public/$file is missing.\n");
        $allDone = false;
    }
}

if ($allDone) {
    echo "\nOK. All changes have been made and all required files are present.\n";
} else {
    fwrite(STDERR, "\nError: Some changes could not be made or some files are missing.\n");
}

// Suggested commit message:
// "Fix myId reference in render.js and verify presence of all required public files"
?>