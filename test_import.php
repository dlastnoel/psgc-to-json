<?php

// Set memory limit BEFORE loading Laravel
ini_set('memory_limit', '2048M');
ini_set('max_execution_time', '0');

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Starting import...\n";
echo "Memory limit: " . ini_get('memory_limit') . "\n";

$filePath = 'storage/app/psgc/PSGC-4Q-2025-Publication-Datafile.xlsx';
$filename = basename($filePath);

try {
    echo "Creating importer...\n";
    $importer = new \App\Actions\Psgc\ImportPsgcData($filePath, $filename, null);
    echo "Importer created\n";
    
    echo "Starting import...\n";
    $result = $importer->execute();
    echo "Import completed\n";
    
    if ($result['success']) {
        echo "Import successful!\n";
        echo "Regions: {$result['regions']}\n";
        echo "Provinces: {$result['provinces']}\n";
        echo "Cities/Municipalities: {$result['cities_municipalities']}\n";
        echo "Barangays: {$result['barangays']}\n";
    } else {
        echo "Import failed: {$result['message']}\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
