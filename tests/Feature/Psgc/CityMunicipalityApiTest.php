<?php

use App\Models\PsgcVersion;
use App\Models\CityMunicipality;
use App\Models\Province;
use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    CityMunicipality::query()->forceDelete();
    Province::query()->forceDelete();
    Region::query()->forceDelete();
    PsgcVersion::query()->forceDelete();
});

it('returns list of cities/municipalities for current version', function () {
    $currentVersion = PsgcVersion::factory()->current()->create();
    $historicalVersion = PsgcVersion::factory()->historical()->create();

    CityMunicipality::factory()->count(3)->create(['psgc_version_id' => $historicalVersion->id]);
    $currentCities = CityMunicipality::factory()->count(2)->create(['psgc_version_id' => $currentVersion->id]);

    $response = $this->getJson('/api/psgc/cities-municipalities');

    $response->assertStatus(200);
    $response->assertJsonCount(2, 'data');
});

it('filters cities/municipalities by province_id', function () {
    $version = PsgcVersion::factory()->current()->create();

    $province1 = Province::factory()->create(['psgc_version_id' => $version->id]);
    $province2 = Province::factory()->create(['psgc_version_id' => $version->id]);

    CityMunicipality::factory()->count(3)->create([
        'psgc_version_id' => $version->id,
        'province_id' => $province1->id,
    ]);
    CityMunicipality::factory()->count(2)->create([
        'psgc_version_id' => $version->id,
        'province_id' => $province2->id,
    ]);

    $response = $this->getJson('/api/psgc/cities-municipalities?province_id='.$province1->id);

    $response->assertStatus(200);
    $response->assertJsonCount(3, 'data');
});

it('filters cities/municipalities by region_id', function () {
    $version = PsgcVersion::factory()->current()->create();

    $region1 = Region::factory()->create(['psgc_version_id' => $version->id]);
    $region2 = Region::factory()->create(['psgc_version_id' => $version->id]);

    $province1 = Province::factory()->create(['psgc_version_id' => $version->id, 'region_id' => $region1->id]);
    $province2 = Province::factory()->create(['psgc_version_id' => $version->id, 'region_id' => $region2->id]);

    CityMunicipality::factory()->count(3)->create([
        'psgc_version_id' => $version->id,
        'province_id' => $province1->id,
    ]);
    CityMunicipality::factory()->count(2)->create([
        'psgc_version_id' => $version->id,
        'province_id' => $province2->id,
    ]);

    $response = $this->getJson('/api/psgc/cities-municipalities?region_id='.$region1->id);

    $response->assertStatus(200);
    $response->assertJsonCount(3, 'data');
});

it('returns cities/municipalities for specific version', function () {
    $version1 = PsgcVersion::factory()->create();
    $version2 = PsgcVersion::factory()->create();

    CityMunicipality::factory()->count(3)->create(['psgc_version_id' => $version1->id]);
    CityMunicipality::factory()->count(2)->create(['psgc_version_id' => $version2->id]);

    $response = $this->getJson('/api/psgc/cities-municipalities?version='.$version1->id);

    $response->assertStatus(200);
    $response->assertJsonCount(3, 'data');
});

it('filters by multiple parameters', function () {
    $version1 = PsgcVersion::factory()->create();
    $version2 = PsgcVersion::factory()->create();

    $region = Region::factory()->create(['psgc_version_id' => $version1->id]);
    $province = Province::factory()->create(['psgc_version_id' => $version1->id, 'region_id' => $region->id]);

    CityMunicipality::factory()->count(3)->create([
        'psgc_version_id' => $version1->id,
        'province_id' => $province->id,
    ]);
    CityMunicipality::factory()->count(2)->create([
        'psgc_version_id' => $version2->id,
        'province_id' => $province->id,
    ]);

    $response = $this->getJson('/api/psgc/cities-municipalities?province_id='.$province->id.'&version='.$version1->id);

    $response->assertStatus(200);
    $response->assertJsonCount(3, 'data');
});

it('includes barangays when loaded', function () {
    $version = PsgcVersion::factory()->current()->create();
    CityMunicipality::factory()->create(['psgc_version_id' => $version->id]);

    $response = $this->getJson('/api/psgc/cities-municipalities');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            '*' => [
                'id',
                'name',
                'code',
                'barangays',
            ],
        ],
    ]);
});

it('returns single city/municipality with full hierarchy', function () {
    $version = PsgcVersion::factory()->current()->create();
    $city = CityMunicipality::factory()->create(['psgc_version_id' => $version->id]);

    $response = $this->getJson('/api/psgc/cities-municipalities/'.$city->id);

    $response->assertStatus(200);
    $response->assertJson([
        'data' => [
            'id' => $city->id,
            'code' => $city->code,
            'name' => $city->name,
            'psgc_version_id' => $version->id,
        ],
    ]);
});

it('returns 404 for non-existent city/municipality', function () {
    $response = $this->getJson('/api/psgc/cities-municipalities/99999');

    $response->assertStatus(404);
});

it('returns empty when no current version exists', function () {
    $version = PsgcVersion::factory()->historical()->create();
    CityMunicipality::factory()->count(5)->create(['psgc_version_id' => $version->id]);

    $response = $this->getJson('/api/psgc/cities-municipalities');

    $response->assertStatus(200);
    $response->assertJsonCount(0, 'data');
});
