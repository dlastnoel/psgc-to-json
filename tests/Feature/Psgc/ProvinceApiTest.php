<?php

use App\Http\Resources\ProvinceResource;
use App\Models\PsgcVersion;
use App\Models\Province;
use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Province::query()->forceDelete();
    Region::query()->forceDelete();
    PsgcVersion::query()->forceDelete();
});

it('returns list of provinces for current version', function () {
    $currentVersion = PsgcVersion::factory()->current()->create();
    $historicalVersion = PsgcVersion::factory()->historical()->create();

    Province::factory()->count(3)->create(['psgc_version_id' => $historicalVersion->id]);
    $currentProvinces = Province::factory()->count(2)->create(['psgc_version_id' => $currentVersion->id]);

    $response = $this->getJson('/api/psgc/provinces');

    $response->assertStatus(200);
    $response->assertJsonCount(2, 'data');
});

it('filters provinces by region_id', function () {
    $version = PsgcVersion::factory()->current()->create();

    $region1 = Region::factory()->create(['psgc_version_id' => $version->id]);
    $region2 = Region::factory()->create(['psgc_version_id' => $version->id]);

    Province::factory()->count(3)->create([
        'psgc_version_id' => $version->id,
        'region_id' => $region1->id,
    ]);
    Province::factory()->count(2)->create([
        'psgc_version_id' => $version->id,
        'region_id' => $region2->id,
    ]);

    $response = $this->getJson('/api/psgc/provinces?region_id='.$region1->id);

    $response->assertStatus(200);
    $response->assertJsonCount(3, 'data');
});

it('returns provinces for specific version', function () {
    $version1 = PsgcVersion::factory()->create();
    $version2 = PsgcVersion::factory()->create();

    Province::factory()->count(3)->create(['psgc_version_id' => $version1->id]);
    Province::factory()->count(2)->create(['psgc_version_id' => $version2->id]);

    $response = $this->getJson('/api/psgc/provinces?version='.$version1->id);

    $response->assertStatus(200);
    $response->assertJsonCount(3, 'data');
});

it('filters by both version and region_id', function () {
    $version1 = PsgcVersion::factory()->create();
    $version2 = PsgcVersion::factory()->create();

    $region = Region::factory()->create(['psgc_version_id' => $version1->id]);

    Province::factory()->count(3)->create([
        'psgc_version_id' => $version1->id,
        'region_id' => $region->id,
    ]);
    Province::factory()->count(2)->create([
        'psgc_version_id' => $version2->id,
        'region_id' => $region->id,
    ]);

    $response = $this->getJson('/api/psgc/provinces?region_id='.$region->id.'&version='.$version1->id);

    $response->assertStatus(200);
    $response->assertJsonCount(3, 'data');
});

it('includes cities_municipalities when loaded', function () {
    $version = PsgcVersion::factory()->current()->create();
    $province = Province::factory()->create(['psgc_version_id' => $version->id]);

    $response = $this->getJson('/api/psgc/provinces');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            '*' => [
                'id',
                'name',
                'code',
                'cities_municipalities',
            ],
        ],
    ]);
});

it('returns single province with full hierarchy', function () {
    $version = PsgcVersion::factory()->current()->create();
    $province = Province::factory()->create(['psgc_version_id' => $version->id]);

    $response = $this->getJson('/api/psgc/provinces/'.$province->id);

    $response->assertStatus(200);
    $response->assertJson([
        'data' => [
            'id' => $province->id,
            'code' => $province->code,
            'name' => $province->name,
            'psgc_version_id' => $version->id,
        ],
    ]);
});

it('returns 404 for non-existent province', function () {
    $response = $this->getJson('/api/psgc/provinces/99999');

    $response->assertStatus(404);
});
