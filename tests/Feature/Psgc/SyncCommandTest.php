<?php

use App\Actions\Psgc\ImportPsgcData;
use App\Actions\Psgc\ValidatePsgcExcel;
use App\Models\PsgcVersion;
use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Clean database before each test
    Region::query()->forceDelete();
    PsgcVersion::query()->forceDelete();

    // Clean storage
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

it('successfully syncs with valid Excel file', function () {
    $validExcelPath = createMockExcelFile([
        createRow('0100000000', 'Region I', '0100000000', 'Region'),
    ]);

    // Mock the validation to always pass
    ValidatePsgcExcel::shouldReceive('run')
        ->once()
        ->andReturn(new class {
            public function isValid() { return true; }
            public function getErrors() { return []; }
        });

    $this->artisan('psgc:sync', ['--path' => $validExcelPath])
        ->expectsOutput('Phase 1: Downloading PSGC file...')
        ->expectsOutput('Phase 2: Validating PSGC file...')
        ->expectsOutput('Phase 3: Importing PSGC data...')
        ->expectsOutput('Import completed successfully')
        ->assertSuccessful();

    // Verify version was created
    expect(PsgcVersion::count())->toBe(1);

    $version = PsgcVersion::first();
    expect($version->is_current)->toBeTrue();

    // Cleanup
    unlink($validExcelPath);
});

it('creates new version on each sync', function () {
    $validExcelPath = createMockExcelFile([
        createRow('0100000000', 'Region I', '0100000000', 'Region'),
    ]);

    // Mock validation
    ValidatePsgcExcel::shouldReceive('run')
        ->andReturn(new class {
            public function isValid() { return true; }
            public function getErrors() { return []; }
        });

    // First sync
    $this->artisan('psgc:sync', ['--path' => $validExcelPath])->assertSuccessful();
    expect(PsgcVersion::count())->toBe(1);

    $version1 = PsgcVersion::first();

    // Second sync (same file)
    $this->artisan('psgc:sync', ['--path' => $validExcelPath])->assertSuccessful();
    expect(PsgcVersion::count())->toBe(2);

    $version1->refresh();
    expect($version1->is_current)->toBeFalse();

    $version2 = PsgcVersion::current()->first();
    expect($version2->id)->not->toBe($version1->id);

    // Cleanup
    unlink($validExcelPath);
});

it('displays import summary with version info', function () {
    $validExcelPath = createMockExcelFile([
        createRow('0100000000', 'Region I', '0100000000', 'Region'),
    ]);

    // Mock validation
    ValidatePsgcExcel::shouldReceive('run')
        ->andReturn(new class {
            public function isValid() { return true; }
            public function getErrors() { return []; }
        });

    $this->artisan('psgc:sync', ['--path' => $validExcelPath, '--force' => true])
        ->expectsOutput('Import Summary:')
        ->expectsOutput('PSGC Version ID:')
        ->expectsOutput('Imported Version: 4Q 2025')
        ->assertSuccessful();

    // Cleanup
    unlink($validExcelPath);
});

it('skips validation with --force flag', function () {
    $invalidExcelPath = createMockExcelFile([
        createRow('0100000000', 'Region I', '0100000000', 'Region'),
    ]);

    // Mock validation - should not be called with --force
    ValidatePsgcExcel::shouldNotReceive('run');

    $this->artisan('psgc:sync', ['--path' => $invalidExcelPath, '--force' => true])
        ->expectsOutput('Skipping validation (--force flag is set)')
        ->assertSuccessful();

    // Cleanup
    unlink($invalidExcelPath);
});

it('fails when file does not exist', function () {
    $nonExistentPath = '/nonexistent/path/to/file.xlsx';

    $this->artisan('psgc:sync', ['--path' => $nonExistentPath])
        ->expectsOutput('Phase 1: Downloading PSGC file...')
        ->expectsOutput('Local file not found: '.$nonExistentPath)
        ->assertExitCode(2);
});

it('queues job with --queue flag', function () {
    $this->artisan('psgc:sync', ['--queue'])
        ->expectsOutput('Dispatching PSGC sync to queue...')
        ->expectsOutput('Job dispatched successfully!')
        ->assertSuccessful();

    // Verify job was dispatched
    $this->assertJobsDispatched(\App\Jobs\SyncPsgcJob::class);
});

it('fails without --path when crawling is not available', function () {
    $this->artisan('psgc:sync')
        ->expectsOutput('Phase 1: Downloading PSGC file...')
        ->expectsOutput('Crawling PSA website for latest publication...')
        ->expectsOutput('Failed to find latest PSGC publication URL.')
        ->assertExitCode(2);
});

// Helper functions

function createMockExcelFile(array $rows): string
{
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
    $worksheet = $spreadsheet->getActiveSheet();

    // Header row
    $worksheet->setCellValue('A1', '10-digit PSGC');
    $worksheet->setCellValue('B1', 'Name');
    $worksheet->setCellValue('C1', 'Correspondence Code');
    $worksheet->setCellValue('D1', 'Geographic Level');

    $row = 2;
    foreach ($rows as $rowData) {
        $worksheet->setCellValue('A'.$row, $rowData['code']);
        $worksheet->setCellValue('B'.$row, $rowData['name']);
        $worksheet->setCellValue('C'.$row, $rowData['correspondence_code']);
        $worksheet->setCellValue('D'.$row, $rowData['geographic_level']);

        if (isset($rowData['region_code'])) {
            $worksheet->setCellValue('E'.$row, $rowData['region_code']);
        }

        if (isset($rowData['province_code'])) {
            $worksheet->setCellValue('F'.$row, $rowData['province_code']);
        }

        if (isset($rowData['city_code'])) {
            $worksheet->setCellValue('G'.$row, $rowData['city_code']);
        }

        $row++;
    }

    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
    $tempPath = storage_path('app/test_sync_'.time().'.xlsx');
    $writer->save($tempPath);

    return $tempPath;
}

function createRow(string $code, string $name, string $correspondenceCode, string $geoLevel, ?string $regionCode = null, ?string $provinceCode = null, ?string $cityCode = null): array
{
    return [
        'code' => $code,
        'name' => $name,
        'correspondence_code' => $correspondenceCode,
        'geographic_level' => $geoLevel,
        'region_code' => $regionCode,
        'province_code' => $provinceCode,
        'city_code' => $cityCode,
    ];
}
