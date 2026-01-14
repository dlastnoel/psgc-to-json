<?php

use App\Models\PsgcVersion;
use App\Models\Province;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Province::query()->forceDelete();
    PsgcVersion::query()->forceDelete();
});

it('filters to current version with current() scope', function () {
    $historicalVersion = PsgcVersion::factory()->historical()->create();
    $currentVersion = PsgcVersion::factory()->current()->create();

    Province::factory()->count(3)->create(['psgc_version_id' => $historicalVersion->id]);
    Province::factory()->count(2)->create(['psgc_version_id' => $currentVersion->id]);

    $currentProvinces = Province::current()->get();

    expect($currentProvinces)->toHaveCount(2);
    expect($currentProvinces->first()->psgc_version_id)->toBe($currentVersion->id);
});

it('filters to specific version with version() scope', function () {
    $version1 = PsgcVersion::factory()->create();
    $version2 = PsgcVersion::factory()->create();

    Province::factory()->count(3)->create(['psgc_version_id' => $version1->id]);
    Province::factory()->count(2)->create(['psgc_version_id' => $version2->id]);

    $version1Provinces = Province::version($version1->id)->get();

    expect($version1Provinces)->toHaveCount(3);
    expect($version1Provinces->first()->psgc_version_id)->toBe($version1->id);
});

it('returns empty when no current version exists', function () {
    $version = PsgcVersion::factory()->historical()->create();
    Province::factory()->count(5)->create(['psgc_version_id' => $version->id]);

    $currentProvinces = Province::current()->get();

    expect($currentProvinces)->toHaveCount(0);
});

it('combines version filter with other filters', function () {
    $version = PsgcVersion::factory()->current()->create();

    $province1 = Province::factory()->create([
        'psgc_version_id' => $version->id,
        'code' => '0110000000',
        'name' => 'Province A',
    ]);
    $province2 = Province::factory()->create([
        'psgc_version_id' => $version->id,
        'code' => '0120000000',
        'name' => 'Province B',
    ]);

    $filteredProvinces = Province::current()
        ->where('code', '0110000000')
        ->get();

    expect($filteredProvinces)->toHaveCount(1);
    expect($filteredProvinces->first()->name)->toBe('Province A');
});

it('works with relationships loaded', function () {
    $version = PsgcVersion::factory()->current()->create();
    $province = Province::factory()->create(['psgc_version_id' => $version->id]);

    $provincesWithCities = Province::current()->with('citiesMunicipalities')->get();

    expect($provincesWithCities)->toHaveCount(1);
    expect($provincesWithCities->first()->relationLoaded('citiesMunicipalities'))->toBeTrue();
});
