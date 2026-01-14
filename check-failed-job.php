<?php

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$failedJob = \Illuminate\Support\Facades\DB::table('failed_jobs')->first();

if ($failedJob) {
    echo "Exception: " . $failedJob->exception . PHP_EOL;
} else {
    echo "No failed jobs found." . PHP_EOL;
}
