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

$cityClasses = [];
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
    
    if ($normalizedLevel === 'city' || $normalizedLevel === 'mun' || $normalizedLevel === 'municipality') {
        if ($cityClass) {
            if (!isset($cityClasses[$cityClass])) {
                $cityClasses[$cityClass] = [];
            }
            $cityClasses[$cityClass][] = "$name ($code)";
        }
    }
    
    if ($normalizedLevel === 'city') {
        if ($cityClass === 'HUC' || $cityClass === 'ICC') {
            $elevatedCities[] = "$name ($code)";
        }
    }
}

echo "City Classes found:\n";
foreach ($cityClasses as $class => $cities) {
    echo "  $class: " . count($cities) . " cities\n";
}

echo "\nElevated cities (HUC/ICC) found: " . count($elevatedCities) . "\n";
foreach (array_slice($elevatedCities, 0, 10) as $city) {
    echo "  $city\n";
}
