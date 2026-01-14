<?php

use App\Http\Resources\RegionResource;
use App\Models\PsgcVersion;
use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Clean database before each test
    Region::query()->forceDelete();
    PsgcVersion::query()->forceDelete();
});

it('returns list of regions for current version', function () {
    $currentVersion = PsgcVersion::factory()->current()->create();
    $historicalVersion = PsgcVersion::factory()->historical()->create();

    Region::factory()->count(3)->create(['psgc_version_id' => $historicalVersion->id]);
    $currentRegions = Region::factory()->count(2)->create(['psgc_version_id' => $currentVersion->id]);

    $response = $this->getJson('/api/psgc/regions');

    $response->assertStatus(200);
    $response->assertJsonCount(2, 'data');

    foreach ($currentRegions as $region) {
        $response->assertJsonFragment([
            'id' => $region->id,
            'name' => $region->name,
            'code' => $region->code,
        ]);
    }
});

it('returns regions for specific version', function () {
    $version1 = PsgcVersion::factory()->create();
    $version2 = PsgcVersion::factory()->create();

    $regionsV1 = Region::factory()->count(3)->create(['psgc_version_id' => $version1->id]);
    Region::factory()->count(2)->create(['psgc_version_id' => $version2->id]);

    $response = $this->getJson('/api/psgc/regions?version='.$version1->id);

    $response->assertStatus(200);
    $response->assertJsonCount(3, 'data');
});

it('includes provinces when requested', function () {
    $version = PsgcVersion::factory()->current()->create();
    $region = Region::factory()->create(['psgc_version_id' => $version->id]);

    $response = $this->getJson('/api/psgc/regions');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            '*' => [
                'id',
                'code',
                'name',
                'provinces',
            ],
        ],
    ]);
});

it('returns empty array when no current version', function () {
    // Create regions but no current version
    $version = PsgcVersion::factory()->historical()->create();
    Region::factory()->count(5)->create(['psgc_version_id' => $version->id]);

    $response = $this->getJson('/api/psgc/regions');

    $response->assertStatus(200);
    $response->assertJsonCount(0, 'data');
});

it('returns single region with full hierarchy', function () {
    $version = PsgcVersion::factory()->current()->create();
    $region = Region::factory()->create(['psgc_version_id' => $version->id]);

    $response = $this->getJson('/api/psgc/regions/'.$region->id);

    $response->assertStatus(200);
    $response->assertJson([
        'data' => [
            'id' => $region->id,
            'code' => $region->code,
            'name' => $region->name,
            'psgc_version_id' => $version->id,
        ],
    ]);
});

it('returns 404 for non-existent region', function () {
    $response = $this->getJson('/api/psgc/regions/99999');

    $response->assertStatus(404);
});

it('filters region by version parameter', function () {
    $version1 = PsgcVersion::factory()->create();
    $version2 = PsgcVersion::factory()->current()->create();

    $regionV1 = Region::factory()->create(['code' => '0100000000', 'psgc_version_id' => $version1->id]);
    $regionV2 = Region::factory()->create(['code' => '0200000000', 'psgc_version_id' => $version2->id]);

    // Default should return version 2 (current)
    $response1 = $this->getJson('/api/psgc/regions/'.$regionV2->code);
    $response1->assertStatus(200);
    $response1->assertJsonFragment(['code' => $regionV2->code]);

    // Explicit version should return version 1
    $response2 = $this->getJson('/api/psgc/regions/'.$regionV1->code.'?version='.$version1->id);
    $response2->assertStatus(200);
    $response2->assertJsonFragment(['code' => $regionV1->code]);
});
