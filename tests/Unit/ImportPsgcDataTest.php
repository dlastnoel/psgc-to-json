<?php

use App\Actions\Psgc\ImportPsgcData;
use App\Models\PsgcVersion;
use App\Models\Region;
use App\Models\Province;
use App\Models\CityMunicipality;
use App\Models\Barangay;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

beforeEach(function () {
    // Clean database before each test
    PsgcVersion::query()->forceDelete();
    Region::query()->forceDelete();
    Province::query()->forceDelete();
    CityMunicipality::query()->forceDelete();
    Barangay::query()->forceDelete();
});

it('creates a new PsgcVersion on import', function () {
    $filePath = createMockExcelFile([
        createRow('0100000000', 'Region I', '0100000000', 'Region'),
    ]);

    ImportPsgcData::run($filePath, 'PSGC-4Q-2025-Publication-Datafile.xlsx', 'https://test.com/file.xlsx');

    $versions = PsgcVersion::all();
    expect($versions)->toHaveCount(1);
    expect($versions->first()->year)->toBe('2025');
    expect($versions->first()->quarter)->toBe('4Q');
    expect($versions->first()->filename)->toBe('PSGC-4Q-2025-Publication-Datafile.xlsx');
    expect($versions->first()->download_url)->toBe('https://test.com/file.xlsx');
});

it('marks imported version as current', function () {
    $filePath = createMockExcelFile([
        createRow('0100000000', 'Region I', '0100000000', 'Region'),
    ]);

    ImportPsgcData::run($filePath, 'PSGC-4Q-2025-Publication-Datafile.xlsx');

    $currentVersion = PsgcVersion::getCurrentVersion();
    expect($currentVersion)->not->toBeNull();
    expect($currentVersion->is_current)->toBeTrue();
});

it('imports regions correctly', function () {
    $filePath = createMockExcelFile([
        createRow('0100000000', 'Region I', '0100000000', 'Region'),
        createRow('0200000000', 'Region II', '0200000000', 'Region'),
    ]);

    $result = ImportPsgcData::run($filePath);

    expect($result['success'])->toBeTrue();
    expect(Region::count())->toBe(2);

    $region1 = Region::where('code', '0100000000')->first();
    expect($region1->name)->toBe('Region I');

    $region2 = Region::where('code', '0200000000')->first();
    expect($region2->name)->toBe('Region II');
});

it('imports provinces correctly', function () {
    $filePath = createMockExcelFile([
        createRow('0100000000', 'Region I', '0100000000', 'Region'),
        createRow('0110000000', 'Province 1', '0110000000', 'Province', '0100000000'),
        createRow('0120000000', 'Province 2', '0120000000', 'Province', '0100000000'),
    ]);

    $result = ImportPsgcData::run($filePath);

    expect($result['success'])->toBeTrue();
    expect(Province::count())->toBe(2);

    $province1 = Province::where('code', '0110000000')->first();
    expect($province1->name)->toBe('Province 1');

    $province2 = Province::where('code', '0120000000')->first();
    expect($province2->name)->toBe('Province 2');
});

it('imports cities/municipalities correctly', function () {
    $filePath = createMockExcelFile([
        createRow('0100000000', 'Region I', '0100000000', 'Region'),
        createRow('0110000000', 'Province 1', '0110000000', 'Province', '0100000000'),
        createRow('0110100000', 'City 1', '0110100000', 'City', '0100000000', '0110000000'),
        createRow('0110200000', 'Municipality 1', '0110200000', 'Municipality', '0100000000', '0110000000'),
    ]);

    $result = ImportPsgcData::run($filePath);

    expect($result['success'])->toBeTrue();
    expect(CityMunicipality::count())->toBe(2);

    $city = CityMunicipality::where('code', '0110100000')->first();
    expect($city->name)->toBe('City 1');

    $municipality = CityMunicipality::where('code', '0110200000')->first();
    expect($municipality->name)->toBe('Municipality 1');
});

it('imports barangays correctly', function () {
    $filePath = createMockExcelFile([
        createRow('0100000000', 'Region I', '0100000000', 'Region'),
        createRow('0110000000', 'Province 1', '0110000000', 'Province', '0100000000'),
        createRow('0110100000', 'City 1', '0110100000', 'City', '0100000000', '0110000000'),
        createRow('0110100001', 'Barangay 1', '0110100001', 'Barangay', '0100000000', '0110000000', '0110100000'),
        createRow('0110100002', 'Barangay 2', '0110100002', 'Barangay', '0100000000', '0110000000', '0110100000'),
    ]);

    $result = ImportPsgcData::run($filePath);

    expect($result['success'])->toBeTrue();
    expect(Barangay::count())->toBe(2);

    $barangay1 = Barangay::where('code', '0110100001')->first();
    expect($barangay1->name)->toBe('Barangay 1');

    $barangay2 = Barangay::where('code', '0110100002')->first();
    expect($barangay2->name)->toBe('Barangay 2');
});

it('links imported data to psgc_version_id', function () {
    $filePath = createMockExcelFile([
        createRow('0100000000', 'Region I', '0100000000', 'Region'),
        createRow('0110000000', 'Province 1', '0110000000', 'Province', '0100000000'),
    ]);

    ImportPsgcData::run($filePath);

    $version = PsgcVersion::first();
    $region = Region::where('code', '0100000000')->first();
    $province = Province::where('code', '0110000000')->first();

    expect($region->psgc_version_id)->toBe($version->id);
    expect($province->psgc_version_id)->toBe($version->id);
});

it('updates version counts correctly', function () {
    $filePath = createMockExcelFile([
        createRow('0100000000', 'Region I', '0100000000', 'Region'),
        createRow('0110000000', 'Province 1', '0110000000', 'Province', '0100000000'),
        createRow('0110100000', 'City 1', '0110100000', 'City', '0100000000', '0110000000'),
        createRow('0110100001', 'Barangay 1', '0110100001', 'Barangay', '0100000000', '0110000000', '0110100000'),
    ]);

    $result = ImportPsgcData::run($filePath);

    $version = PsgcVersion::first();
    expect($version->regions_count)->toBe(1);
    expect($version->provinces_count)->toBe(1);
    expect($version->cities_municipalities_count)->toBe(1);
    expect($version->barangays_count)->toBe(1);
});

it('establishes relationships between levels', function () {
    $filePath = createMockExcelFile([
        createRow('0100000000', 'Region I', '0100000000', 'Region'),
        createRow('0110000000', 'Province 1', '0110000000', 'Province', '0100000000'),
        createRow('0110100000', 'City 1', '0110100000', 'City', '0100000000', '0110000000'),
        createRow('0110100001', 'Barangay 1', '0110100001', 'Barangay', '0100000000', '0110000000', '0110100000'),
    ]);

    ImportPsgcData::run($filePath);

    $region = Region::where('code', '0100000000')->first();
    $province = Province::where('code', '0110000000')->first();
    $city = CityMunicipality::where('code', '0110100000')->first();
    $barangay = Barangay::where('code', '0110100001')->first();

    expect($province->region_id)->toBe($region->id);
    expect($city->province_id)->toBe($province->id);
    expect($barangay->province_id)->toBe($province->id);
    expect($barangay->city_municipality_id)->toBe($city->id);
});

it('extracts version info from filename', function () {
    $filePath = createMockExcelFile([
        createRow('0100000000', 'Region I', '0100000000', 'Region'),
    ]);

    ImportPsgcData::run($filePath, 'PSGC-3Q-2024-Publication-Datafile.xlsx');

    $version = PsgcVersion::first();
    expect($version->quarter)->toBe('3Q');
    expect($version->year)->toBe('2024');
});

it('returns import summary with correct structure', function () {
    $filePath = createMockExcelFile([
        createRow('0100000000', 'Region I', '0100000000', 'Region'),
        createRow('0110000000', 'Province 1', '0110000000', 'Province', '0100000000'),
        createRow('0110100000', 'City 1', '0110100000', 'City', '0100000000', '0110000000'),
    ]);

    $result = ImportPsgcData::run($filePath);

    expect($result)->toHaveKey('success');
    expect($result)->toHaveKey('psgc_version_id');
    expect($result)->toHaveKey('quarter');
    expect($result)->toHaveKey('year');
    expect($result)->toHaveKey('regions');
    expect($result)->toHaveKey('provinces');
    expect($result)->toHaveKey('cities_municipalities');
    expect($result)->toHaveKey('barangays');

    expect($result['success'])->toBeTrue();
    expect($result['regions'])->toBe(1);
});

// Helper functions

function createMockExcelFile(array $rows): string
{
    $spreadsheet = new Spreadsheet;
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

    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $tempPath = storage_path('app/test_import_'.time().'.xlsx');
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
