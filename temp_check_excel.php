<?php

require __DIR__.'/bootstrap/app.php';

$file = storage_path('app/psgc/PSGC-4Q-2025-Publication-Datafile.xlsx');
$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
$sheet = $spreadsheet->getSheetByName('PSGC');

if ($sheet) {
    $row = $sheet->getRowIterator()->current();
    if ($row) {
        echo "Column Headers in PSGC sheet:\n";
        foreach ($row as $cell) {
            echo "- $cell\n";
        }
    } else {
        echo "Sheet is empty\n";
    }
} else {
    echo "PSGC sheet not found\n";
}
