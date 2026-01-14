<?php

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Regions: " . \App\Models\Region::count() . PHP_EOL;
echo "Provinces: " . \App\Models\Province::count() . PHP_EOL;
echo "Cities: " . \App\Models\CityMunicipality::count() . PHP_EOL;
echo "Barangays: " . \App\Models\Barangay::count() . PHP_EOL;
