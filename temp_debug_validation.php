<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

use App\Actions\Psgc\ValidatePsgcExcel;

$file = storage_path('app/psgc/PSGC-4Q-2025-Publication-Datafile.xlsx');

echo "Validating file: $file\n";
echo str_repeat("=", 80)."\n\n";

$result = ValidatePsgcExcel::run($file);

echo "Is valid: ".($result->isValid() ? 'YES' : 'NO')."\n\n";

if (!$result->isValid()) {
    echo "Errors:\n";
    foreach ($result->getErrors() as $error) {
        echo "  - $error\n";
    }
}
