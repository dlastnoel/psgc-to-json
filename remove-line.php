<?php

// Read file
$file = __DIR__ . '/app/Actions/Psgc/ImportPsgcData.php';
$content = file_get_contents($file);

// Split by newlines
$lines = explode("\n", $content);

// Remove line at index 56 (which is line 57, counting from 1)
unset($lines[56]);

// Join back
$newContent = implode("\n", $lines);

// Write back
file_put_contents($file, $newContent);

echo "Removed line 57 (index 56)\n";
echo "Total lines before: " . count(explode("\n", $content)) . "\n";
echo "Total lines after: " . count($lines) . "\n";
