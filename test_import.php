<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

try {
    $result = (new App\Actions\Psgc\ImportPsgcData('/var/www/html/storage/app/psgc/PSGC-4Q-2025-Publication-Datafile.xlsx'))->execute();

    echo 'Result: ';
    print_r($result);
} catch (Exception $e) {
    echo 'Error: '.$e->getMessage().PHP_EOL;
    echo 'File: '.$e->getFile().PHP_EOL;
    echo 'Line: '.$e->getLine().PHP_EOL;
}
