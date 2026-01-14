<?php

use App\Models\Barangay;
use App\Models\CityMunicipality;
use App\Models\PsgcVersion;
use App\Models\Province;
use App\Models\Region;

beforeEach(function () {
    // Clean database before each test
    PsgcVersion::query()->forceDelete();
    Region::query()->forceDelete();
    Province::query()->forceDelete();
    CityMunicipality::query()->forceDelete();
    Barangay::query()->forceDelete();
});

it('can create a PSGC version', function () {
    $version = PsgcVersion::factory()->create([
        'quarter' => '4Q',
        'year' => '2025',
        'publication_date' => '2025-12-15',
        'download_url' => 'https://psa.gov.ph/file.xlsx',
        'filename' => 'PSGC-4Q-2025-Publication-Datafile.xlsx',
    ]);

    expect($version->id)->toBeInt();
    expect($version->quarter)->toBe('4Q');
    expect($version->year)->toBe('2025');
    expect($version->download_url)->toBe('https://psa.gov.ph/file.xlsx');
    expect($version->is_current)->toBeFalse();
});

it('has regions relationship', function () {
    $version = PsgcVersion::factory()->create();
    $region = Region::factory()->create(['psgc_version_id' => $version->id]);

    expect($version->regions)->toHaveCount(1);
    expect($version->regions->first()->id)->toBe($region->id);
});

it('has provinces relationship', function () {
    $version = PsgcVersion::factory()->create();
    $province = Province::factory()->create(['psgc_version_id' => $version->id]);

    expect($version->provinces)->toHaveCount(1);
    expect($version->provinces->first()->id)->toBe($province->id);
});

it('has cities_municipalities relationship', function () {
    $version = PsgcVersion::factory()->create();
    $city = CityMunicipality::factory()->create(['psgc_version_id' => $version->id]);

    expect($version->citiesMunicipalities)->toHaveCount(1);
    expect($version->citiesMunicipalities->first()->id)->toBe($city->id);
});

it('has barangays relationship', function () {
    $version = PsgcVersion::factory()->create();
    $barangay = Barangay::factory()->create(['psgc_version_id' => $version->id]);

    expect($version->barangays)->toHaveCount(1);
    expect($version->barangays->first()->id)->toBe($barangay->id);
});

it('can set version as current', function () {
    $version1 = PsgcVersion::factory()->create(['is_current' => true]);
    $version2 = PsgcVersion::factory()->create(['is_current' => false]);
    $version3 = PsgcVersion::factory()->create(['is_current' => false]);

    $version2->setCurrent();

    // Refresh from database
    $version1->refresh();
    $version2->refresh();
    $version3->refresh();

    expect($version1->fresh()->is_current)->toBeFalse();
    expect($version2->fresh()->is_current)->toBeTrue();
    expect($version3->fresh()->is_current)->toBeFalse();
});

it('getCurrentVersion returns current version', function () {
    $version1 = PsgcVersion::factory()->create(['is_current' => false]);
    $version2 = PsgcVersion::factory()->create(['is_current' => true]);
    $version3 = PsgcVersion::factory()->create(['is_current' => false]);

    $current = PsgcVersion::getCurrentVersion();

    expect($current->id)->toBe($version2->id);
});

it('getCurrentVersion returns null when no current version', function () {
    PsgcVersion::factory()->create(['is_current' => false]);
    PsgcVersion::factory()->create(['is_current' => false]);

    $current = PsgcVersion::getCurrentVersion();

    expect($current)->toBeNull();
});

it('scopeCurrent filters to current version only', function () {
    $version1 = PsgcVersion::factory()->create(['is_current' => false]);
    $version2 = PsgcVersion::factory()->create(['is_current' => true]);
    $version3 = PsgcVersion::factory()->create(['is_current' => false]);

    $currentVersions = PsgcVersion::current()->get();

    expect($currentVersions)->toHaveCount(1);
    expect($currentVersions->first()->id)->toBe($version2->id);
});

it('casts publication_date to date', function () {
    $version = PsgcVersion::factory()->create([
        'publication_date' => '2025-12-15 10:30:00',
    ]);

    expect($version->publication_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
    expect($version->publication_date->format('Y-m-d'))->toBe('2025-12-15');
});

it('casts is_current to boolean', function () {
    $version = PsgcVersion::factory()->create(['is_current' => 1]);

    expect($version->is_current)->toBeBool();
    expect($version->is_current)->toBeTrue();
});

it('stores record counts', function () {
    $version = PsgcVersion::factory()->create([
        'regions_count' => 17,
        'provinces_count' => 82,
        'cities_municipalities_count' => 163,
        'barangays_count' => 42000,
    ]);

    expect($version->regions_count)->toBe(17);
    expect($version->provinces_count)->toBe(82);
    expect($version->cities_municipalities_count)->toBe(163);
    expect($version->barangays_count)->toBe(42000);
});

it('has fillable fields', function () {
    $version = PsgcVersion::factory()->create([
        'quarter' => '1Q',
        'year' => '2026',
        'publication_date' => now(),
        'download_url' => 'https://example.com/file.xlsx',
        'filename' => 'test.xlsx',
        'is_current' => false,
        'regions_count' => 10,
        'provinces_count' => 50,
        'cities_municipalities_count' => 100,
        'barangays_count' => 20000,
    ]);

    $version->refresh();

    expect($version->quarter)->toBe('1Q');
    expect($version->year)->toBe('2026');
    expect($version->download_url)->toBe('https://example.com/file.xlsx');
    expect($version->filename)->toBe('test.xlsx');
});
