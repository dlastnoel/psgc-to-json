<?php

require __DIR__.'/vendor/autoload.php';
ini_set('memory_limit', '2048M');
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Check provinces with is_elevated_city flag
echo "Provinces with is_elevated_city flag:\n";
$elevatedCities = \App\Models\Province::where('is_elevated_city', true)->limit(10)->get(['id', 'code', 'name', 'region_code', 'province_code', 'is_elevated_city']);
foreach ($elevatedCities as $province) {
    echo "  {$province->name} ({$province->code}): region_code={$province->region_code}, province_code={$province->province_code}, is_elevated_city={$province->is_elevated_city}\n";
}

echo "\nAll provinces:\n";
$provinces = \App\Models\Province::limit(20)->get(['id', 'code', 'name', 'region_code', 'province_code']);
foreach ($provinces as $province) {
    echo "  {$province->name} ({$province->code}): region_code={$province->region_code}, province_code={$province->province_code}\n";
}
