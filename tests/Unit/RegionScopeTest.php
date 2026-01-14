<?php

use App\Models\PsgcVersion;
use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Region::query()->forceDelete();
    PsgcVersion::query()->forceDelete();
});

it('filters to current version with current() scope', function () {
    $historicalVersion = PsgcVersion::factory()->historical()->create();
    $currentVersion = PsgcVersion::factory()->current()->create();

    // Historical version regions
    Region::factory()->count(3)->create(['psgc_version_id' => $historicalVersion->id]);
    // Current version regions
    Region::factory()->count(2)->create(['psgc_version_id' => $currentVersion->id]);

    $currentRegions = Region::current()->get();

    expect($currentRegions)->toHaveCount(2);
    expect($currentRegions->first()->psgc_version_id)->toBe($currentVersion->id);
});

it('filters to specific version with version() scope', function () {
    $version1 = PsgcVersion::factory()->create();
    $version2 = PsgcVersion::factory()->create();

    Region::factory()->count(3)->create(['psgc_version_id' => $version1->id]);
    Region::factory()->count(2)->create(['psgc_version_id' => $version2->id]);

    $version1Regions = Region::version($version1->id)->get();

    expect($version1Regions)->toHaveCount(3);
    expect($version1Regions->first()->psgc_version_id)->toBe($version1->id);

    $version2Regions = Region::version($version2->id)->get();

    expect($version2Regions)->toHaveCount(2);
    expect($version2Regions->first()->psgc_version_id)->toBe($version2->id);
});

it('returns empty when no current version exists', function () {
    $version = PsgcVersion::factory()->historical()->create();
    Region::factory()->count(5)->create(['psgc_version_id' => $version->id]);

    $currentRegions = Region::current()->get();

    expect($currentRegions)->toHaveCount(0);
});

it('combines scopes correctly', function () {
    $version1 = PsgcVersion::factory()->create();
    $version2 = PsgcVersion::factory()->current()->create();

    Region::factory()->count(3)->create([
        'psgc_version_id' => $version1->id,
        'code' => '0100000000',
    ]);
    Region::factory()->count(2)->create([
        'psgc_version_id' => $version2->id,
        'code' => '0200000000',
    ]);

    // Use version() scope with current() scope (version() should take precedence)
    $version1Regions = Region::version($version1->id)->current()->get();

    expect($version1Regions)->toHaveCount(3);
    expect($version1Regions->first()->code)->toBe('0100000000');
});

it('works with relationships', function () {
    $version = PsgcVersion::factory()->current()->create();
    $region = Region::factory()->create(['psgc_version_id' => $version->id]);

    $regionsWithProvinces = Region::current()->with('provinces')->get();

    expect($regionsWithProvinces)->toHaveCount(1);
    expect($regionsWithProvinces->first()->relationLoaded('provinces'))->toBeTrue();
});

it('preserves other scopes', function () {
    $version = PsgcVersion::factory()->current()->create();

    Region::factory()->create([
        'psgc_version_id' => $version->id,
        'code' => '0100000000',
        'name' => 'Region A',
    ]);
    Region::factory()->create([
        'psgc_version_id' => $version->id,
        'code' => '0200000000',
        'name' => 'Region B',
    ]);

    $filteredRegions = Region::current()->where('code', '0100000000')->get();

    expect($filteredRegions)->toHaveCount(1);
    expect($filteredRegions->first()->name)->toBe('Region A');
});
