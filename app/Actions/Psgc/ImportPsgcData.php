<?php

namespace App\Actions\Psgc;

use App\Models\Barangay;
use App\Models\CityMunicipality;
use App\Models\PsgcVersion;
use App\Models\Province;
use App\Models\Region;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportPsgcData
{
    protected string $filePath;

    protected array $regions = [];

    protected array $provinces = [];

    protected array $citiesMunicipalities = [];

    protected array $barangays = [];

    protected int $regionsCount = 0;

    protected int $provincesCount = 0;

    protected int $citiesMunicipalitiesCount = 0;

    protected int $barangaysCount = 0;

    protected ?PsgcVersion $psgcVersion = null;

    protected ?string $filename = null;

    protected ?string $downloadUrl = null;

    protected ?string $quarter = null;

    protected ?string $year = null;

    public function __construct(string $filePath, ?string $filename = null, ?string $downloadUrl = null)
    {
        $this->filePath = $filePath;
        $this->filename = $filename;
        $this->downloadUrl = $downloadUrl;
    }

    public static function run(string $filePath, ?string $filename = null, ?string $downloadUrl = null): array
    {
        $importer = new self($filePath, $filename, $downloadUrl);

        return $importer->execute();
    }

    public function execute(): array
    {
        $spreadsheet = IOFactory::load($this->filePath);
        $sheet = $spreadsheet->getSheetByName(config('psgc.validation.required_sheet'));

        if (! $sheet) {
            return [
                'success' => false,
                'message' => 'PSGC sheet not found',
            ];
        }

        $this->parseSheet($sheet);
        $this->extractVersionInfo();
        $this->createPsgcVersion();
        $this->establishRelationships();
        $this->saveData();
        $this->updatePsgcVersionCounts();
        $this->markVersionAsCurrent();

        return [
            'success' => true,
            'psgc_version_id' => $this->psgcVersion->id,
            'quarter' => $this->quarter,
            'year' => $this->year,
            'regions' => $this->regionsCount,
            'provinces' => $this->provincesCount,
            'cities_municipalities' => $this->citiesMunicipalitiesCount,
            'barangays' => $this->barangaysCount,
        ];
    }

    protected function parseSheet($sheet): void
    {
        $headers = [];
        $headerRowFound = false;

        foreach ($sheet->getRowIterator() as $rowIndex => $row) {
            $rowData = [];

            foreach ($row->getCellIterator() as $cell) {
                $rowData[] = (string) $cell->getValue();
            }

            if (! $headerRowFound) {
                $headers = $rowData;
                $headerRowFound = true;

                continue;
            }

            $this->processRow($headers, $rowData);
        }
    }

    protected function processRow(array $headers, array $rowData): void
    {
        $data = array_combine($headers, $rowData);

        $code = $data['10-digit PSGC'] ?? '';
        $name = $data['Name'] ?? '';
        $correspondenceCode = $data['Correspondence Code'] ?? '';
        $geographicLevel = trim($data['Geographic Level'] ?? '');

        if (empty($code) || empty($name) || empty($geographicLevel)) {
            return;
        }

        $normalizedLevel = strtolower($geographicLevel);

        // Map abbreviations to full level names
        $levelMap = [
            'reg' => 'Region',
            'prov' => 'Province',
            'city' => 'City',
            'mun' => 'Municipality',
            'bgy' => 'Barangay',
            'submun' => 'SubMun',
        ];

        $mappedLevel = $levelMap[$normalizedLevel] ?? $geographicLevel;

        if ($normalizedLevel === 'reg' || $normalizedLevel === 'region') {
            $this->regions[$code] = [
                'code' => $code,
                'name' => $name,
                'correspondence_code' => $correspondenceCode,
                'geographic_level' => $mappedLevel,
            ];
        } elseif ($normalizedLevel === 'prov' || $normalizedLevel === 'province') {
            $this->provinces[$code] = [
                'code' => $code,
                'name' => $name,
                'correspondence_code' => $correspondenceCode,
                'geographic_level' => $mappedLevel,
            ];
        } elseif (in_array($normalizedLevel, ['city', 'mun', 'municipality', 'submun'], true)) {
            $this->citiesMunicipalities[$code] = [
                'code' => $code,
                'name' => $name,
                'correspondence_code' => $correspondenceCode,
                'geographic_level' => $mappedLevel,
            ];
        } elseif ($normalizedLevel === 'bgy' || $normalizedLevel === 'barangay') {
            $this->barangays[$code] = [
                'code' => $code,
                'name' => $name,
                'correspondence_code' => $correspondenceCode,
                'geographic_level' => $mappedLevel,
            ];
        }
    }

    protected function establishRelationships(): void
    {
        // Establish province -> region relationships using PSGC code prefix
        foreach ($this->provinces as $code => &$province) {
            $regionPrefix = substr($code, 0, 2); // First 2 digits = region
            $regionCode = $regionPrefix . '00000000'; // Match 10-digit region code

            if (isset($this->regions[$regionCode])) {
                $province['region_id'] = $this->regions[$regionCode]['id'] ?? null;
            }
        }

        // Establish city -> region and city -> province relationships
        foreach ($this->citiesMunicipalities as $code => &$cityMunicipality) {
            $regionPrefix = substr($code, 0, 2); // First 2 digits = region
            $provincePrefix = substr($code, 0, 6); // First 6 digits = province

            $regionCode = $regionPrefix . '00000000'; // Match 10-digit region code
            $provinceCode = $provincePrefix . '0000'; // Match 10-digit province code

            if (isset($this->regions[$regionCode])) {
                $cityMunicipality['region_id'] = $this->regions[$regionCode]['id'] ?? null;
            }

            if (isset($this->provinces[$provinceCode])) {
                $cityMunicipality['province_id'] = $this->provinces[$provinceCode]['id'] ?? null;
            }
        }
    }

    protected function establishBarangayRelationships(): void
    {
        // Establish barangay relationships (only run after cities are saved so they have IDs)
        foreach ($this->barangays as $code => &$barangay) {
            $regionPrefix = substr($code, 0, 2); // First 2 digits = region
            $provincePrefix = substr($code, 0, 6); // First 6 digits = province
            $cityPrefix = substr($code, 0, 8); // First 8 digits = city/municipality

            $regionCode = $regionPrefix . '00000000'; // Match 10-digit region code
            $provinceCode = $provincePrefix . '0000'; // Match 10-digit province code
            $cityCode = $cityPrefix . '00'; // Match 10-digit city/municipality code

            if (isset($this->regions[$regionCode])) {
                $barangay['region_id'] = $this->regions[$regionCode]['id'] ?? null;
            }

            if (isset($this->provinces[$provinceCode])) {
                $barangay['province_id'] = $this->provinces[$provinceCode]['id'] ?? null;
            }

            if (isset($this->citiesMunicipalities[$cityCode])) {
                $barangay['city_municipality_id'] = $this->citiesMunicipalities[$cityCode]['id'] ?? null;
            }
        }
    }

    protected function saveData(): void
    {
        DB::transaction(function () {
            $this->saveRegions();
            $this->establishRelationships();
            $this->saveProvinces();
            $this->saveCitiesMunicipalities();
            $this->establishBarangayRelationships();
            $this->saveBarangays();
        });
    }

    protected function saveRegions(): void
    {
        foreach ($this->regions as $code => &$region) {
            $region['psgc_version_id'] = $this->psgcVersion->id;
            $created = Region::updateOrCreate(
                ['code' => $code, 'psgc_version_id' => $this->psgcVersion->id],
                [
                    'name' => $region['name'],
                    'correspondence_code' => $region['correspondence_code'],
                    'geographic_level' => $region['geographic_level'],
                    'psgc_version_id' => $this->psgcVersion->id,
                ]
            );

            $region['id'] = $created->id;

            if ($created->wasRecentlyCreated) {
                $this->regionsCount++;
            }
        }
    }

    protected function saveProvinces(): void
    {
        foreach ($this->provinces as $code => &$province) {
            $province['psgc_version_id'] = $this->psgcVersion->id;
            $created = Province::updateOrCreate(
                ['code' => $code, 'psgc_version_id' => $this->psgcVersion->id],
                [
                    'name' => $province['name'],
                    'correspondence_code' => $province['correspondence_code'],
                    'geographic_level' => $province['geographic_level'],
                    'region_id' => $province['region_id'] ?? null,
                    'psgc_version_id' => $this->psgcVersion->id,
                ]
            );

            $province['id'] = $created->id;

            if ($created->wasRecentlyCreated) {
                $this->provincesCount++;
            }
        }
    }

    protected function saveCitiesMunicipalities(): void
    {
        foreach ($this->citiesMunicipalities as $code => &$cityMunicipality) {
            $cityMunicipality['psgc_version_id'] = $this->psgcVersion->id;
            $data = [
                'name' => $cityMunicipality['name'],
                'correspondence_code' => $cityMunicipality['correspondence_code'],
                'geographic_level' => $cityMunicipality['geographic_level'],
                'region_id' => $cityMunicipality['region_id'] ?? null,
                'province_id' => $cityMunicipality['province_id'] ?? null,
                'is_capital' => false, // PSGC doesn't indicate capitals in data structure
                'psgc_version_id' => $this->psgcVersion->id,
            ];

            $created = CityMunicipality::updateOrCreate(
                ['code' => $code, 'psgc_version_id' => $this->psgcVersion->id],
                $data
            );

            $cityMunicipality['id'] = $created->id;

            if ($created->wasRecentlyCreated) {
                $this->citiesMunicipalitiesCount++;
            }
        }
    }

    protected function saveBarangays(): void
    {
        foreach ($this->barangays as $code => $barangay) {
            $barangay['psgc_version_id'] = $this->psgcVersion->id;
            $data = [
                'code' => $code,
                'name' => $barangay['name'],
                'correspondence_code' => $barangay['correspondence_code'],
                'geographic_level' => $barangay['geographic_level'],
                'psgc_version_id' => $this->psgcVersion->id,
            ];

            if (isset($barangay['region_id'])) {
                $data['region_id'] = $barangay['region_id'];
            }

            if (isset($barangay['province_id'])) {
                $data['province_id'] = $barangay['province_id'];
            }

            if (isset($barangay['city_municipality_id'])) {
                $data['city_municipality_id'] = $barangay['city_municipality_id'];
            }

            $created = Barangay::updateOrCreate(
                ['code' => $code, 'psgc_version_id' => $this->psgcVersion->id],
                $data
            );

            if ($created->wasRecentlyCreated) {
                $this->barangaysCount++;
            }
        }
    }

    /**
     * Extract version information from filename.
     */
    protected function extractVersionInfo(): void
    {
        if ($this->filename === null) {
            return;
        }

        $pattern = config('psgc.filename_pattern');

        if (preg_match($pattern, basename($this->filename), $matches)) {
            $this->quarter = $matches[1];
            $this->year = $matches[2];
        }
    }

    /**
     * Create new PsgcVersion record.
     */
    protected function createPsgcVersion(): void
    {
        $this->psgcVersion = new PsgcVersion([
            'quarter' => $this->quarter ?? null,
            'year' => $this->year ?? null,
            'publication_date' => now(),
            'download_url' => $this->downloadUrl,
            'filename' => $this->filename,
            'is_current' => false,
        ]);

        $this->psgcVersion->save();
    }

    /**
     * Update PsgcVersion with record counts.
     */
    protected function updatePsgcVersionCounts(): void
    {
        $this->psgcVersion->update([
            'regions_count' => $this->regionsCount,
            'provinces_count' => $this->provincesCount,
            'cities_municipalities_count' => $this->citiesMunicipalitiesCount,
            'barangays_count' => $this->barangaysCount,
        ]);
    }

    /**
     * Mark this version as current.
     */
    protected function markVersionAsCurrent(): void
    {
        $this->psgcVersion->setCurrent();
    }
}
