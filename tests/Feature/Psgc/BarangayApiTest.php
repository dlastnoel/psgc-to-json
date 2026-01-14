<?php

use App\Http\Resources\BarangayResource;
use App\Models\PsgcVersion;
use App\Models\Barangay;
use App\Models\CityMunicipality;
use App\Models\Province;
use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Barangay::query()->forceDelete();
    CityMunicipality::query()->forceDelete();
    Province::query()->forceDelete();
    Region::query()->forceDelete();
    PsgcVersion::query()->forceDelete();
});

it('returns list of barangays for current version', function () {
    $currentVersion = PsgcVersion::factory()->current()->create();
    $historicalVersion = PsgcVersion::factory()->historical()->create();

    Barangay::factory()->count(5)->create(['psgc_version_id' => $historicalVersion->id]);
    $currentBarangays = Barangay::factory()->count(3)->create(['psgc_version_id' => $currentVersion->id]);

    $response = $this->getJson('/api/psgc/barangays');

    $response->assertStatus(200);
    $response->assertJsonCount(3, 'data');
});

it('filters barangays by city_municipality_id', function () {
    $version = PsgcVersion::factory()->current()->create();

    $city1 = CityMunicipality::factory()->create(['psgc_version_id' => $version->id]);
    $city2 = CityMunicipality::factory()->create(['psgc_version_id' => $version->id]);

    Barangay::factory()->count(3)->create([
        'psgc_version_id' => $version->id,
        'city_municipality_id' => $city1->id,
    ]);
    Barangay::factory()->count(2)->create([
        'psgc_version_id' => $version->id,
        'city_municipality_id' => $city2->id,
    ]);

    $response = $this->getJson('/api/psgc/barangays?city_municipality_id='.$city1->id);

    $response->assertStatus(200);
    $response->assertJsonCount(3, 'data');
});

it('filters barangays by province_id', function () {
    $version = PsgcVersion::factory()->current()->create();

    $province1 = Province::factory()->create(['psgc_version_id' => $version->id]);
    $province2 = Province::factory()->create(['psgc_version_id' => $version->id]);

    $city1 = CityMunicipality::factory()->create(['psgc_version_id' => $version->id, 'province_id' => $province1->id]);
    $city2 = CityMunicipality::factory()->create(['psgc_version_id' => $version->id, 'province_id' => $province2->id]);

    Barangay::factory()->count(3)->create([
        'psgc_version_id' => $version->id,
        'city_municipality_id' => $city1->id,
    ]);
    Barangay::factory()->count(2)->create([
        'psgc_version_id' => $version->id,
        'city_municipality_id' => $city2->id,
    ]);

    $response = $this->getJson('/api/psgc/barangays?province_id='.$province1->id);

    $response->assertStatus(200);
    $response->assertJsonCount(3, 'data');
});

it('filters barangays by region_id', function () {
    $version = PsgcVersion::factory()->current()->create();

    $region1 = Region::factory()->create(['psgc_version_id' => $version->id]);
    $region2 = Region::factory()->create(['psgc_version_id' => $version->id]);

    $province1 = Province::factory()->create(['psgc_version_id' => $version->id, 'region_id' => $region1->id]);
    $province2 = Province::factory()->create(['psgc_version_id' => $version->id, 'region_id' => $region2->id]);

    $city1 = CityMunicipality::factory()->create(['psgc_version_id' => $version->id, 'province_id' => $province1->id]);
    $city2 = CityMunicipality::factory()->create(['psgc_version_id' => $version->id, 'province_id' => $province2->id]);

    Barangay::factory()->count(3)->create([
        'psgc_version_id' => $version->id,
        'city_municipality_id' => $city1->id,
    ]);
    Barangay::factory()->count(2)->create([
        'psgc_version_id' => $version->id,
        'city_municipality_id' => $city2->id,
    ]);

    $response = $this->getJson('/api/psgc/barangays?region_id='.$region1->id);

    $response->assertStatus(200);
    $response->assertJsonCount(3, 'data');
});

it('returns barangays for specific version', function () {
    $version1 = PsgcVersion::factory()->create();
    $version2 = PsgcVersion::factory()->create();

    Barangay::factory()->count(3)->create(['psgc_version_id' => $version1->id]);
    Barangay::factory()->count(2)->create(['psgc_version_id' => $version2->id]);

    $response = $this->getJson('/api/psgc/barangays?version='.$version1->id);

    $response->assertStatus(200);
    $response->assertJsonCount(3, 'data');
});

it('filters by multiple parameters', function () {
    $version1 = PsgcVersion::factory()->create();
    $version2 = PsgcVersion::factory()->create();

    $region = Region::factory()->create(['psgc_version_id' => $version1->id]);
    $province = Province::factory()->create(['psgc_version_id' => $version1->id, 'region_id' => $region->id]);
    $city = CityMunicipality::factory()->create(['psgc_version_id' => $version1->id, 'province_id' => $province->id]);

    Barangay::factory()->count(3)->create([
        'psgc_version_id' => $version1->id,
        'city_municipality_id' => $city->id,
    ]);
    Barangay::factory()->count(2)->create([
        'psgc_version_id' => $version2->id,
        'city_municipality_id' => $city->id,
    ]);

    $response = $this->getJson('/api/psgc/barangays?city_municipality_id='.$city->id.'&version='.$version1->id);

    $response->assertStatus(200);
    $response->assertJsonCount(3, 'data');
});

it('returns single barangay with parent hierarchy', function () {
    $version = PsgcVersion::factory()->current()->create();
    $region = Region::factory()->create(['psgc_version_id' => $version->id]);
    $province = Province::factory()->create(['psgc_version_id' => $version->id, 'region_id' => $region->id]);
    $city = CityMunicipality::factory()->create(['psgc_version_id' => $version->id, 'province_id' => $province->id]);
    $barangay = Barangay::factory()->create([
        'psgc_version_id' => $version->id,
        'city_municipality_id' => $city->id,
        'province_id' => $province->id,
        'region_id' => $region->id,
    ]);

    $response = $this->getJson('/api/psgc/barangays/'.$barangay->id);

    $response->assertStatus(200);
    $response->assertJson([
        'data' => [
            'id' => $barangay->id,
            'code' => $barangay->code,
            'name' => $barangay->name,
            'psgc_version_id' => $version->id,
        ],
    ]);
});

it('returns 404 for non-existent barangay', function () {
    $response = $this->getJson('/api/psgc/barangays/99999');

    $response->assertStatus(404);
});

it('returns empty when no current version exists', function () {
    // Create barangays but no current version
    $version = PsgcVersion::factory()->historical()->create();
    Barangay::factory()->count(5)->create(['psgc_version_id' => $version->id]);

    $response = $this->getJson('/api/psgc/barangays');

    $response->assertStatus(200);
    $response->assertJsonCount(0, 'data');
});
