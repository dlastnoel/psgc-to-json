<?php

// Read the original file
$filePath = __DIR__ . '/app/Actions/Psgc/ImportPsgcData.php';
$content = file_get_contents($filePath);

// Find and remove the duplicate establishRelationships call
// Lines to remove:
// - Empty line after saveData()
// - Comment line
// - establishRelationships() call

$pattern = '/(\s*\$this->saveData\(\);\s*)(\s*\/\/ Then establish relationships using IDs\s*\$this->establishRelationships\(\);\s*)/';
$replacement = '$1';

$newContent = preg_replace($pattern, $replacement, $content);

// Write the modified content back
file_put_contents($filePath, $newContent);

echo "Successfully removed duplicate establishRelationships() call\n";
echo "File modified: $filePath\n";
