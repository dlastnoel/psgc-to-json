<?php

namespace App\Console\Commands;

use App\Actions\Psgc\ImportPsgcData;
use App\Models\Barangay;
use App\Models\CityMunicipality;
use App\Models\Province;
use App\Models\Region;
use Illuminate\Console\Command;

class TestPsgcImplementation extends Command
{
    protected $signature = 'psgc:test-implementation';

    protected $description = 'Test PSGC import implementation with new features';

    protected bool $allPassed = true;

    public function handle()
    {
        $this->info('=== Testing PSGC Import with New Features ===');
        $this->newLine();

        // Import test file
        $filePath = storage_path('app/test_psgc.xlsx');
        $this->info("Importing test file: {$filePath}");
        $this->newLine();

        if (! file_exists($filePath)) {
            $this->error('Test file not found! Run create_test_excel.php first.');
            return Command::FAILURE;
        }

        $result = ImportPsgcData::run($filePath, 'test_psgc.xlsx', 'https://test.com/file.xlsx');

        if (! $result['success']) {
            $this->error("Import failed: {$result['message']}");
            return Command::FAILURE;
        }

        $this->info('✓ Import successful');
        $this->line("  PSGC Version ID: {$result['psgc_version_id']}");
        $this->newLine();

        $versionId = $result['psgc_version_id'];

        $this->testRegionData($versionId);
        $this->testProvinceData($versionId);
        $this->testCitiesMunicipalities($versionId);
        $this->testNCRMunicipality($versionId);
        $this->testBarangayData($versionId);
        $this->printSummary($versionId);

        return $this->allPassed ? Command::SUCCESS : Command::FAILURE;
    }

    protected function testRegionData(int $versionId): void
    {
        $this->info('=== Testing Region Data ===');
        $ncr = Region::where('code', '1300000000')->where('psgc_version_id', $versionId)->first();

        if (! $ncr) {
            $this->error('✗ NCR Region not found');
            $this->allPassed = false;

            return;
        }

        $this->line('✓ NCR Region found');
        $this->line("  Name: {$ncr->name}");
        $this->line("  Old Name: " . ($ncr->old_name ?? 'NULL'));

        if ($ncr->old_name === 'Metro Manila') {
            $this->info('✓ Old Name correctly preserved');
        } else {
            $this->error('✗ Old Name NOT preserved (expected: Metro Manila, got: ' . ($ncr->old_name ?? 'NULL') . ')');
            $this->allPassed = false;
        }

        $this->newLine();
    }

    protected function testProvinceData(int $versionId): void
    {
        $this->info('=== Testing Province Data ===');

        // Test 1: Regular Province
        $this->info('1. Regular Province (Ilocos Norte):');
        $ilocosNorte = Province::where('code', '0128000000')->where('psgc_version_id', $versionId)->first();

        if (! $ilocosNorte) {
            $this->error('  ✗ Not found');
            $this->allPassed = false;

            return;
        }

        $this->line('  ✓ Found');
        $this->line("  Name: {$ilocosNorte->name}");
        $this->line("  Old Name: " . ($ilocosNorte->old_name ?? 'NULL'));
        $this->line("  Region ID: " . ($ilocosNorte->region_id ?? 'NULL'));
        $this->line("  Is Elevated: " . ($ilocosNorte->is_elevated_city ? 'Yes' : 'No'));
        $this->line("  Is Capital: " . ($ilocosNorte->is_capital ? 'Yes' : 'No'));

        $pass = $ilocosNorte->old_name === 'Ilocos Norte'
            && ! $ilocosNorte->is_elevated_city
            && ! $ilocosNorte->is_capital
            && $ilocosNorte->region_id; // Check that region_id is set

        if ($pass) {
            $this->info('  ✓ Province correctly imported WITH region_id');
        } else {
            $this->error('  ✗ Province NOT correctly imported');
            $this->allPassed = false;
        }
        $this->newLine();

        // Test 2: NCR City (Manila) - should be elevated
        $this->info('2. NCR City (Manila - should be elevated to provinces):');
        $manila = Province::where('code', '1375040000')->where('psgc_version_id', $versionId)->first();

        if (! $manila) {
            $this->error('  ✗ Not found in provinces table');
            $this->allPassed = false;

            return;
        }

        $this->line('  ✓ Found in provinces table');
        $this->line("  Name: {$manila->name}");
        $this->line("  Old Name: " . ($manila->old_name ?? 'NULL'));
        $this->line("  Geographic Level: {$manila->geographic_level}");
        $this->line("  Is Elevated: " . ($manila->is_elevated_city ? 'Yes' : 'No'));
        $this->line("  Is Capital: " . ($manila->is_capital ? 'Yes' : 'No'));

        $pass = $manila->old_name === 'Manila'
            && $manila->is_elevated_city
            && $manila->geographic_level === 'City'
            && $manila->is_capital;

        if ($pass) {
            $this->info('  ✓ Manila correctly elevated and flagged as capital');
        } else {
            $this->error('  ✗ Manila NOT correctly imported');
            $this->allPassed = false;
        }
        $this->newLine();

        // Test 3: NCR City (Quezon City) - should be elevated but not capital
        $this->info('3. NCR City (Quezon City - should be elevated but not capital):');
        $quezonCity = Province::where('code', '1375050000')->where('psgc_version_id', $versionId)->first();

        if (! $quezonCity) {
            $this->error('  ✗ Not found in provinces table');
            $this->allPassed = false;

            return;
        }

        $this->line('  ✓ Found in provinces table');
        $this->line("  Name: {$quezonCity->name}");
        $this->line("  Old Name: " . ($quezonCity->old_name ?? 'NULL'));
        $this->line("  Geographic Level: {$quezonCity->geographic_level}");
        $this->line("  Is Elevated: " . ($quezonCity->is_elevated_city ? 'Yes' : 'No'));
        $this->line("  Is Capital: " . ($quezonCity->is_capital ? 'Yes' : 'No'));

        $pass = $quezonCity->old_name === 'Quezon City'
            && $quezonCity->is_elevated_city
            && $quezonCity->geographic_level === 'City'
            && ! $quezonCity->is_capital;

        if ($pass) {
            $this->info('  ✓ Quezon City correctly elevated and NOT flagged as capital');
        } else {
            $this->error('  ✗ Quezon City NOT correctly imported');
            $this->allPassed = false;
        }
        $this->newLine();

        // Test 4: NCR City (Makati) - should be elevated
        $this->info('4. NCR City (Makati - should be elevated to provinces):');
        $makati = Province::where('code', '1376060000')->where('psgc_version_id', $versionId)->first();

        if (! $makati) {
            $this->error('  ✗ Not found in provinces table');
            $this->allPassed = false;

            return;
        }

        $this->line('  ✓ Found in provinces table');
        $this->line("  Name: {$makati->name}");
        $this->line("  Old Name: " . ($makati->old_name ?? 'NULL'));
        $this->line("  Geographic Level: {$makati->geographic_level}");
        $this->line("  Is Elevated: " . ($makati->is_elevated_city ? 'Yes' : 'No'));
        $this->line("  Is Capital: " . ($makati->is_capital ? 'Yes' : 'No'));

        $pass = $makati->old_name === 'Makati'
            && $makati->is_elevated_city
            && $makati->geographic_level === 'City'
            && ! $makati->is_capital;

        if ($pass) {
            $this->info('  ✓ Makati correctly elevated and NOT flagged as capital');
        } else {
            $this->error('  ✗ Makati NOT correctly imported');
            $this->allPassed = false;
        }
        $this->newLine();
    }

    protected function testCitiesMunicipalities(int $versionId): void
    {
        $this->info('=== Testing Non-NCR City (Laoag) ===');
        $laoag = CityMunicipality::where('code', '0128010000')->where('psgc_version_id', $versionId)->first();

        if (! $laoag) {
            $this->error('✗ Not found in cities_municipalities table');
            $this->allPassed = false;

            return;
        }

        $this->info('✓ Found in cities_municipalities table');
        $this->line("  Name: {$laoag->name}");
        $this->line("  Old Name: " . ($laoag->old_name ?? 'NULL'));
        $this->line("  Geographic Level: {$laoag->geographic_level}");
        $this->line("  Is Capital: " . ($laoag->is_capital ? 'Yes' : 'No'));
        $this->line("  Province ID: " . ($laoag->province_id ?? 'NULL'));

        $pass = $laoag->old_name === 'Laoag City'
            && $laoag->geographic_level === 'City'
            && $laoag->is_capital
            && $laoag->province_id;

        if ($pass) {
            $this->info('✓ Laoag correctly imported as capital with province_id');
        } else {
            $this->error('✗ Laoag NOT correctly imported');
            $this->allPassed = false;
        }

        $this->newLine();

        // Test SubMun
        $this->info('=== Testing SubMun (1st District Manila) ===');
        $district = CityMunicipality::where('code', '1375010000')->where('psgc_version_id', $versionId)->first();

        if (! $district) {
            $this->error('✗ SubMun not found in cities_municipalities table');
            $this->allPassed = false;

            return;
        }

        $this->info('✓ Found in cities_municipalities table (should NOT be elevated to provinces)');
        $this->line("  Name: {$district->name}");
        $this->line("  Old Name: " . ($district->old_name ?? 'NULL'));
        $this->line("  Geographic Level: {$district->geographic_level}");
        $this->line("  Province ID: " . ($district->province_id ?? 'NULL'));

        // NCR SubMun should have province_id set to elevated city's ID
        $pass = $district->old_name === '1st District'
            && $district->geographic_level === 'SubMun'
            && $district->province_id; // Should have province_id (Manila's ID)

        if ($pass) {
            $this->info('✓ SubMun correctly in cities_municipalities table WITH province_id (elevated city)');
        } else {
            $this->error('✗ SubMun NOT correctly imported');
            $this->allPassed = false;
        }

        $this->newLine();
    }

    protected function testNCRMunicipality(int $versionId): void
    {
        $this->info('=== Testing NCR Municipality (Pateros) ===');
        $pateros = CityMunicipality::where('code', '1376090000')->where('psgc_version_id', $versionId)->first();

        if (! $pateros) {
            $this->error('✗ NCR Municipality not found in cities_municipalities table');
            $this->allPassed = false;

            return;
        }

        $this->info('✓ Found in cities_municipalities table (should NOT be elevated to provinces)');
        $this->line("  Name: {$pateros->name}");
        $this->line("  Old Name: " . ($pateros->old_name ?? 'NULL'));
        $this->line("  Geographic Level: {$pateros->geographic_level}");
        $this->line("  Province ID: " . ($pateros->province_id ?? 'NULL'));

        // NCR Municipality should have province_id set to matching elevated city's ID
        $pass = $pateros->old_name === 'Pateros'
            && $pateros->geographic_level === 'Municipality'
            && $pateros->province_id; // Should have province_id (Makati's ID)

        if ($pass) {
            $this->info('✓ NCR Municipality correctly in cities_municipalities table WITH province_id (elevated city)');
        } else {
            $this->error('✗ NCR Municipality NOT correctly imported');
            $this->allPassed = false;
        }

        $this->newLine();
    }

    protected function testBarangayData(int $versionId): void
    {
        $this->info('=== Testing Barangay Data ===');

        // Test 1: NCR barangay (parent is elevated city)
        $this->info('1. NCR Barangay (Manila - parent is elevated city):');
        $manilaBarangay = Barangay::where('code', '1375040001')->where('psgc_version_id', $versionId)->first();

        if (! $manilaBarangay) {
            $this->error('✗ Not found');
            $this->allPassed = false;

            return;
        }

        $this->info('✓ Found');
        $this->line("  Name: {$manilaBarangay->name}");
        $this->line("  Old Name: " . ($manilaBarangay->old_name ?? 'NULL'));
        $this->line("  Region ID: " . ($manilaBarangay->region_id ?? 'NULL'));
        $this->line("  Province ID: " . ($manilaBarangay->province_id ?? 'NULL'));
        $this->line("  City/Municipality ID: " . ($manilaBarangay->city_municipality_id ?? 'NULL'));

        // For NCR barangays, province_id should be set, city_municipality_id should be NULL
        $pass = $manilaBarangay->old_name === 'Barangay 1 (Zone 1)'
            && $manilaBarangay->province_id
            && $manilaBarangay->city_municipality_id === null;

        if ($pass) {
            $this->info('✓ Barangay correctly linked');
        } else {
            $this->error('✗ Barangay NOT correctly linked');
            $this->allPassed = false;
        }

        $this->newLine();

        // Test 2: NCR barangay (parent is Makati elevated city)
        $this->info('2. NCR Barangay (Makati - parent is elevated city):');
        $makatiBarangay = Barangay::where('code', '1376060001')->where('psgc_version_id', $versionId)->first();

        if (! $makatiBarangay) {
            $this->error('✗ Not found');
            $this->allPassed = false;

            return;
        }

        $this->info('✓ Found');
        $this->line("  Name: {$makatiBarangay->name}");
        $this->line("  Old Name: " . ($makatiBarangay->old_name ?? 'NULL'));
        $this->line("  Region ID: " . ($makatiBarangay->region_id ?? 'NULL'));
        $this->line("  Province ID: " . ($makatiBarangay->province_id ?? 'NULL'));
        $this->line("  City/Municipality ID: " . ($makatiBarangay->city_municipality_id ?? 'NULL'));

        // For NCR barangays (parent is elevated city), province_id should be set, city_municipality_id should be NULL
        $pass = $makatiBarangay->old_name === 'Barangay 1 (Poblacion)'
            && $makatiBarangay->province_id
            && is_null($makatiBarangay->city_municipality_id); // Should be NULL

        if ($pass) {
            $this->info('✓ Barangay correctly linked');
        } else {
            $this->error('✗ Barangay NOT correctly linked');
            $this->allPassed = false;
        }

        $this->info('✓ Found');
        $this->line("  Name: {$makatiBarangay->name}");
        $this->line("  Old Name: " . ($makatiBarangay->old_name ?? 'NULL'));
        $this->line("  Region ID: " . ($makatiBarangay->region_id ?? 'NULL'));
        $this->line("  Province ID: " . ($makatiBarangay->province_id ?? 'NULL'));
        $this->line("  City/Municipality ID: " . ($makatiBarangay->city_municipality_id ?? 'NULL'));

        $pass = $makatiBarangay->old_name === 'Barangay 1 (Poblacion)'
            && $makatiBarangay->province_id
            && $makatiBarangay->city_municipality_id; // Should be set to elevated city ID

        if ($pass) {
            $this->info('✓ Barangay correctly linked with both province_id and city_municipality_id');
        } else {
            $this->error('✗ Barangay NOT correctly linked');
            $this->allPassed = false;
        }

        $this->newLine();

        // Test 3: NCR Municipality barangay (parent is NCR Municipality)
        $this->info('3. NCR Municipality Barangay (Pateros - parent is NCR Municipality):');
        $paterosBarangay = Barangay::where('code', '1376090001')->where('psgc_version_id', $versionId)->first();

        if (! $paterosBarangay) {
            $this->error('✗ Not found');
            $this->allPassed = false;

            return;
        }

        $this->info('✓ Found');
        $this->line("  Name: {$paterosBarangay->name}");
        $this->line("  Old Name: " . ($paterosBarangay->old_name ?? 'NULL'));
        $this->line("  Region ID: " . ($paterosBarangay->region_id ?? 'NULL'));
        $this->line("  Province ID: " . ($paterosBarangay->province_id ?? 'NULL'));
        $this->line("  City/Municipality ID: " . ($paterosBarangay->city_municipality_id ?? 'NULL'));

        // For NCR Municipality barangays, province_id should be NULL and city_municipality_id should be set
        $pass = $paterosBarangay->old_name === 'Barangay 1 (Aguho)'
            && is_null($paterosBarangay->province_id)
            && $paterosBarangay->city_municipality_id;

        if ($pass) {
            $this->info('✓ Barangay correctly linked to NCR Municipality without province_id');
        } else {
            $this->error('✗ Barangay NOT correctly linked');
            $this->allPassed = false;
        }

        $this->newLine();

        // Test 4: SubMun barangay (parent is SubMun, not elevated)
        $this->info('4. SubMun Barangay (1st District - parent is SubMun):');
        $districtBarangay = Barangay::where('code', '1375010001')->where('psgc_version_id', $versionId)->first();

        if (! $districtBarangay) {
            $this->error('✗ Not found');
            $this->allPassed = false;

            return;
        }

        $this->info('✓ Found');
        $this->line("  Name: {$districtBarangay->name}");
        $this->line("  Old Name: " . ($districtBarangay->old_name ?? 'NULL'));
        $this->line("  Region ID: " . ($districtBarangay->region_id ?? 'NULL'));
        $this->line("  Province ID: " . ($districtBarangay->province_id ?? 'NULL'));
        $this->line("  City/Municipality ID: " . ($districtBarangay->city_municipality_id ?? 'NULL'));

        // For SubMun barangays in NCR, province_id should be NULL but city_municipality_id should be set
        $pass = $districtBarangay->old_name === 'Barangay 1 (Zone 1)'
            && is_null($districtBarangay->province_id)
            && $districtBarangay->city_municipality_id;

        if ($pass) {
            $this->info('✓ Barangay correctly linked to province and SubMun');
        } else {
            $this->error('✗ Barangay NOT correctly linked');
            $this->allPassed = false;
        }

        $this->newLine();
    }

    protected function printSummary(int $versionId): void
    {
        $this->info('=== Summary ===');
        $this->line("Total Regions: " . Region::where('psgc_version_id', $versionId)->count());
        $this->line("Total Provinces: " . Province::where('psgc_version_id', $versionId)->count());
        $this->line("  - Elevated Cities: " . Province::where('psgc_version_id', $versionId)->where('is_elevated_city', true)->count());
        $this->line("  - Actual Provinces: " . Province::where('psgc_version_id', $versionId)->where('is_elevated_city', false)->count());
        $this->line("  - Capitals: " . Province::where('psgc_version_id', $versionId)->where('is_capital', true)->count());
        $this->line("Total Cities/Municipalities: " . CityMunicipality::where('psgc_version_id', $versionId)->count());
        $this->line("  - Cities: " . CityMunicipality::where('psgc_version_id', $versionId)->where('geographic_level', 'City')->count());
        $this->line("  - Municipalities: " . CityMunicipality::where('psgc_version_id', $versionId)->where('geographic_level', 'Municipality')->count());
        $this->line("    - NCR Municipalities: " . CityMunicipality::where('psgc_version_id', $versionId)->where('geographic_level', 'Municipality')->where('code', 'like', '13%')->count());
        $this->line("  - SubMuns: " . CityMunicipality::where('psgc_version_id', $versionId)->where('geographic_level', 'SubMun')->count());
        $this->line("  - Capitals: " . CityMunicipality::where('psgc_version_id', $versionId)->where('is_capital', true)->count());
        $this->line("  - With province_id set: " . CityMunicipality::where('psgc_version_id', $versionId)->whereNotNull('province_id')->count());
        $this->line("Total Barangays: " . Barangay::where('psgc_version_id', $versionId)->count());
        $this->line("  - With province_id set: " . Barangay::where('psgc_version_id', $versionId)->whereNotNull('province_id')->count());
        $this->line("  - With city_municipality_id set: " . Barangay::where('psgc_version_id', $versionId)->whereNotNull('city_municipality_id')->count());

        $this->newLine();

        if ($this->allPassed) {
            $this->info('=== ✓ ALL TESTS PASSED ===');
        } else {
            $this->error('=== ✗ SOME TESTS FAILED ===');
        }
    }
}
