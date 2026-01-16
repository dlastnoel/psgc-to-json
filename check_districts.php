<?php

require __DIR__.'/vendor/autoload.php';
ini_set('memory_limit', '2048M');
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = 'storage/app/psgc/PSGC-4Q-2025-Publication-Datafile.xlsx';
$spreadsheet = IOFactory::load($filePath);
$sheet = $spreadsheet->getSheetByName('PSGC');

$headers = [];
$headerRowFound = false;

$elevatedCities = [];

foreach ($sheet->getRowIterator() as $rowIndex => $row) {
    $rowData = [];
    foreach ($row->getCellIterator() as $cell) {
        $rowData[] = (string) $cell->getValue();
    }
    
    if (!$headerRowFound) {
        $headers = $rowData;
        $headerRowFound = true;
        continue;
    }
    
    $data = array_combine($headers, $rowData);
    $code = $data['10-digit PSGC'] ?? '';
    $name = $data['Name'] ?? '';
    $geographicLevel = trim($data['Geographic Level'] ?? '');
    $cityClass = $data['City Class'] ?? '';
    
    $normalizedLevel = strtolower($geographicLevel);
    
    // Check if elevated city (HUC or ICC)
    if ($normalizedLevel === 'city' && ($cityClass === 'HUC' || $cityClass === 'ICC')) {
        $cityPrefix = substr($code, 0, 4);
        echo "Elevated city found: $name ($code), prefix: $cityPrefix\n";
        $elevatedCities[] = $code;
    }
}

echo "\nChecking districts/sub-municipalities under elevated cities:\n";
foreach ($sheet->getRowIterator() as $rowIndex => $row) {
    $rowData = [];
    foreach ($row->getCellIterator() as $cell) {
        $rowData[] = (string) $cell->getValue();
    }
    
    if (!$headerRowFound) {
        $headers = $rowData;
        $headerRowFound = true;
        continue;
    }
    
    $data = array_combine($headers, $rowData);
    $code = $data['10-digit PSGC'] ?? '';
    $name = $data['Name'] ?? '';
    $geographicLevel = trim($data['Geographic Level'] ?? '');
    
    $normalizedLevel = strtolower($geographicLevel);
    
    if (in_array($normalizedLevel, ['mun', 'municipality', 'submun'], true)) {
        $cityPrefix = substr($code, 0, 4);
        if (in_array($cityPrefix . '000000', $elevatedCities)) {
            echo "$name ($code) - prefix: $cityPrefix - matches elevated city\n";
        }
    }
}
