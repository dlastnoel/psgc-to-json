<?php

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Jobs in queue: " . \Illuminate\Support\Facades\DB::table('jobs')->count() . PHP_EOL;
