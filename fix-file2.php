<?php

// Read original file
$filePath = __DIR__ . '/app/Actions/Psgc/ImportPsgcData.php';
$lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

// Find line numbers to modify
$newLines = [];
$skipCount = 0;

foreach ($lines as $index => $line) {
    $lineNum = $index + 1;

    // Skip lines 69, 70, 71
    if ($lineNum >= 69 && $lineNum <= 71) {
        $skipCount++;
        continue;
    }

    $newLines[] = $line;
}

// Write back
file_put_contents($filePath, implode("\n", $newLines));

echo "Removed $skipCount lines (69-71)\n";
echo "File modified successfully\n";
