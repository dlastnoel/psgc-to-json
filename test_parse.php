<?php

// Set memory limit before loading Laravel
ini_set('memory_limit', '2048M');

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Enable query log
use Illuminate\Support\Facades\DB;
DB::enableQueryLog();

$filePath = 'storage/app/psgc/PSGC-4Q-2025-Publication-Datafile.xlsx';
$filename = basename($filePath);

echo "Starting import...\n";

try {
    echo "Loading spreadsheet...\n";
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
    $sheet = $spreadsheet->getSheetByName(config('psgc.validation.required_sheet'));
    
    if (!$sheet) {
        echo "PSGC sheet not found\n";
        exit(1);
    }
    
    echo "Parsing sheet (first 10 rows)...\n";
    
    $headers = [];
    $headerRowFound = false;
    $rowCount = 0;
    
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
        
        if ($rowCount < 10) {
            $data = array_combine($headers, $rowData);
            echo "Row {$rowCount}: {$data['Name']}\n";
        }
        $rowCount++;
        
        if ($rowCount >= 100) {
            break;
        }
    }
    
    echo "Parsed {$rowCount} data rows\n";
    
    echo "Total queries: " . count(DB::getQueryLog()) . "\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
