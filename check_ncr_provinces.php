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
    
    if ($normalizedLevel === 'prov' || $normalizedLevel === 'province') {
        if (substr($code, 0, 2) === '13') {
            echo "Province in NCR: $name ($code)\n";
            if (stripos($name, 'City') !== false || stripos($name, 'Municipality') !== false) {
                echo "  -> This is an elevated city/municipality!\n";
            }
        }
    }
}
