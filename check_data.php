<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Check all psgc_version records
echo "PSGC Versions:\n";
$versions = \App\Models\PsgcVersion::all(['id', 'quarter', 'year', 'is_current', 'regions_count', 'provinces_count', 'cities_municipalities_count', 'barangays_count']);
foreach ($versions as $version) {
    echo "  ID {$version->id}: {$version->quarter} {$version->year} (current: {$version->is_current}) - R:{$version->regions_count} P:{$version->provinces_count} C:{$version->cities_municipalities_count} B:{$version->barangays_count}\n";
}

echo "\nChecking PSGC data...\n";
echo "Regions count: " . \App\Models\Region::count() . "\n";
echo "Provinces count: " . \App\Models\Province::count() . "\n";
echo "Cities/Municipalities count: " . \App\Models\CityMunicipality::count() . "\n";
echo "Barangays count: " . \App\Models\Barangay::count() . "\n";

// Sample cities
echo "\nSample cities:\n";
$cities = \App\Models\CityMunicipality::limit(5)->get(['code', 'name', 'region_id', 'region_code', 'province_id', 'province_code']);
foreach ($cities as $city) {
    echo "  {$city->name}: region_id={$city->region_id}, region_code={$city->region_code}, province_id={$city->province_id}, province_code={$city->province_code}\n";
}

// Sample barangays
echo "\nSample barangays:\n";
$barangays = \App\Models\Barangay::limit(5)->get(['code', 'name', 'region_id', 'region_code', 'province_id', 'province_code', 'city_municipality_id', 'city_municipality_code']);
foreach ($barangays as $barangay) {
    echo "  {$barangay->name}: region_id={$barangay->region_id}, region_code={$barangay->region_code}, province_id={$barangay->province_id}, province_code={$barangay->province_code}, city_municipality_id={$barangay->city_municipality_id}, city_municipality_code={$barangay->city_municipality_code}\n";
}
