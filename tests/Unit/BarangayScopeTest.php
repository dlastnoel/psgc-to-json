<?php

use App\Models\PsgcVersion;
use App\Models\Barangay;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Barangay::query()->forceDelete();
    PsgcVersion::query()->forceDelete();
});

it('filters to current version with current() scope', function () {
    $historicalVersion = PsgcVersion::factory()->historical()->create();
    $currentVersion = PsgcVersion::factory()->current()->create();

    Barangay::factory()->count(3)->create(['psgc_version_id' => $historicalVersion->id]);
    Barangay::factory()->count(2)->create(['psgc_version_id' => $currentVersion->id]);

    $currentBarangays = Barangay::current()->get();

    expect($currentBarangays)->toHaveCount(2);
    expect($currentBarangays->first()->psgc_version_id)->toBe($currentVersion->id);
});

it('filters to specific version with version() scope', function () {
    $version1 = PsgcVersion::factory()->create();
    $version2 = PsgcVersion::factory()->create();

    Barangay::factory()->count(3)->create(['psgc_version_id' => $version1->id]);
    Barangay::factory()->count(2)->create(['psgc_version_id' => $version2->id]);

    $version1Barangays = Barangay::version($version1->id)->get();

    expect($version1Barangays)->toHaveCount(3);
    expect($version1Barangays->first()->psgc_version_id)->toBe($version1->id);
});

it('returns empty when no current version exists', function () {
    $version = PsgcVersion::factory()->historical()->create();
    Barangay::factory()->count(5)->create(['psgc_version_id' => $version->id]);

    $currentBarangays = Barangay::current()->get();

    expect($currentBarangays)->toHaveCount(0);
});

it('combines version filter with other filters', function () {
    $version = PsgcVersion::factory()->current()->create();

    Barangay::factory()->create([
        'psgc_version_id' => $version->id,
        'code' => '0110100001',
        'name' => 'Barangay A',
    ]);
    Barangay::factory()->create([
        'psgc_version_id' => $version->id,
        'code' => '0110100002',
        'name' => 'Barangay B',
    ]);

    $filteredBarangays = Barangay::current()
        ->where('code', '0110100001')
        ->get();

    expect($filteredBarangays)->toHaveCount(1);
    expect($filteredBarangays->first()->name)->toBe('Barangay A');
});

it('works with relationships loaded', function () {
    $version = PsgcVersion::factory()->current()->create();
    $barangay = Barangay::factory()->create(['psgc_version_id' => $version->id]);

    $barangaysWithCity = Barangay::current()->with('cityMunicipality')->get();

    expect($barangaysWithCity)->toHaveCount(1);
    expect($barangaysWithCity->first()->relationLoaded('cityMunicipality'))->toBeTrue();
});
