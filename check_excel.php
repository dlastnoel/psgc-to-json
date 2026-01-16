<?php

require __DIR__.'/vendor/autoload.php';

ini_set('memory_limit', '2048M');

use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = 'storage/app/psgc/PSGC-4Q-2025-Publication-Datafile.xlsx';

echo "Loading Excel file...\n";
$spreadsheet = IOFactory::load($filePath);
echo "Excel loaded\n";

$sheet = $spreadsheet->getSheetByName('PSGC');
if (!$sheet) {
    echo "PSGC sheet not found\n";
    exit(1);
}
echo "PSGC sheet found\n";

$data = $sheet->toArray();
echo "Total rows: " . count($data) . "\n";
echo "Header row:\n";
print_r($data[0]);
