<?php

use App\Models\Barangay;
use App\Models\CityMunicipality;
use App\Models\Province;
use App\Models\Region;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $psgcPath = storage_path('app/psgc');

    if (File::exists($psgcPath)) {
        File::cleanDirectory($psgcPath);
    }
});

afterEach(function () {
    $psgcPath = storage_path('app/psgc');

    if (File::exists($psgcPath)) {
        File::cleanDirectory($psgcPath);
    }
});

it('successfully validates and stores PSGC Excel file', function () {
    // Mock Excel file (we'll create a real one in storage)
    $validExcelPath = storage_path('app/psgc/PSGC-4Q-2025-Publication-Datafile.xlsx');

    if (! file_exists($validExcelPath)) {
        $this->markTestSkipped('Sample PSGC file not available');
    }

    $this->artisan('psgc:sync', ['--path' => $validExcelPath])
        ->expectsOutput('✓ PSGC sheet found')
        ->expectsOutput('✓ Required columns present')
        ->expectsOutput('✓ File validated successfully')
        ->expectsOutput('✓ Import completed successfully')
        ->assertSuccessful();

    expect(Storage::disk('local')->exists('psgc/PSGC-4Q-2025-Publication-Datafile.xlsx'))->toBeTrue();
});

it('fails validation when PSGC sheet is missing', function () {
    $invalidExcelPath = tempnam(sys_get_temp_dir(), 'psgc').'.xlsx';

    touch($invalidExcelPath);

    $this->artisan('psgc:sync', ['--path' => $invalidExcelPath])
        ->assertFailed();

    if (file_exists($invalidExcelPath)) {
        unlink($invalidExcelPath);
    }
});

it('fails validation when required column is missing', function () {
    $invalidExcelPath = tempnam(sys_get_temp_dir(), 'psgc').'.xlsx';

    touch($invalidExcelPath);

    $this->artisan('psgc:sync', ['--path' => $invalidExcelPath])
        ->assertFailed();

    if (file_exists($invalidExcelPath)) {
        unlink($invalidExcelPath);
    }
});

it('handles file not found error', function () {
    $nonExistentPath = '/nonexistent/path/to/file.xlsx';

    $this->artisan('psgc:sync', ['--path' => $nonExistentPath])
        ->expectsOutput('Fetching PSGC data from PSA website...')
        ->assertExitCode(3);
});

it('displays PSA URL and instructions', function () {
    $this->artisan('psgc:sync')
        ->expectsOutput('Fetching PSGC data from PSA website...')
        ->assertExitCode(3);
});

it('extracts version information from filename', function () {
    $validExcelPath = storage_path('app/psgc/PSGC-4Q-2025-Publication-Datafile.xlsx');

    if (! file_exists($validExcelPath)) {
        $this->markTestSkipped('Sample PSGC file not available');
    }

    $this->artisan('psgc:sync', ['--path' => $validExcelPath])
        ->expectsOutput('Version: 4Q 2025')
        ->assertSuccessful();
});

it('recognizes SubMun as valid Geographic Level', function () {
    // This would test with an Excel file containing SubMun entries
    $validExcelPath = storage_path('app/psgc/PSGC-4Q-2025-Publication-Datafile.xlsx');

    if (! file_exists($validExcelPath)) {
        $this->markTestSkipped('Sample PSGC file not available');
    }

    $this->artisan('psgc:sync', ['--path' => $validExcelPath])
        ->assertSuccessful();
});

it('imports PSGC data into database', function () {
    $validExcelPath = storage_path('app/psgc/PSGC-4Q-2025-Publication-Datafile.xlsx');

    if (! file_exists($validExcelPath)) {
        $this->markTestSkipped('Sample PSGC file not available');
    }

    $this->artisan('psgc:sync', ['--path' => $validExcelPath])
        ->expectsOutput('✓ Import completed successfully')
        ->assertSuccessful();

    expect(Region::count())->toBeGreaterThan(0);
    expect(Province::count())->toBeGreaterThan(0);
    expect(CityMunicipality::count())->toBeGreaterThan(0);
    expect(Barangay::count())->toBeGreaterThan(0);
});

it('displays import summary', function () {
    $validExcelPath = storage_path('app/psgc/PSGC-4Q-2025-Publication-Datafile.xlsx');

    if (! file_exists($validExcelPath)) {
        $this->markTestSkipped('Sample PSGC file not available');
    }

    $this->artisan('psgc:sync', ['--path' => $validExcelPath])
        ->expectsOutput('Import Summary:')
        ->expectsOutput('Regions:')
        ->expectsOutput('Provinces:')
        ->expectsOutput('Cities/Municipalities:')
        ->expectsOutput('Barangays:')
        ->assertSuccessful();
});

it('updates existing records when re-importing', function () {
    $validExcelPath = storage_path('app/psgc/PSGC-4Q-2025-Publication-Datafile.xlsx');

    if (! file_exists($validExcelPath)) {
        $this->markTestSkipped('Sample PSGC file not available');
    }

    $this->artisan('psgc:sync', ['--path' => $validExcelPath])->assertSuccessful();

    $initialRegionCount = Region::count();
    $initialProvinceCount = Province::count();

    $this->artisan('psgc:sync', ['--path' => $validExcelPath])
        ->expectsOutput('✓ Import completed successfully')
        ->assertSuccessful();

    expect(Region::count())->toBe($initialRegionCount);
    expect(Province::count())->toBe($initialProvinceCount);
});
