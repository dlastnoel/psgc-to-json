# NCR Cities Elevation Specification

## Overview

All cities/municipalities under the National Capital Region (NCR) must be elevated to the **provinces** table while maintaining their geographic level as "City".

## Background

The Philippine Standard Geographic Code (PSGC) structure has a special case for the National Capital Region (NCR - Region Code: `13`). NCR does not have traditional provinces; instead, cities/municipalities are directly under NCR.

To maintain a consistent data structure, NCR cities should be stored in the `provinces` table while preserving their actual geographic level.

## Implementation Guides

For detailed implementation guides and step-by-step instructions, please refer to:

📂 **Agent Apollo** - Implementation Guides
📁 Location: `.claude/agents/apollo`

The Apollo agent contains:
- Database migration examples
- Model implementation details
- Import logic modifications
- Testing strategies
- Deployment procedures

## Requirements

### 1. Database Schema Changes

#### Add New Columns to `provinces` Table

```sql
ALTER TABLE provinces ADD COLUMN is_capital BOOLEAN DEFAULT FALSE;
ALTER TABLE provinces ADD COLUMN is_elevated_city BOOLEAN DEFAULT FALSE;
```

**Migration File:**
```php
// database/migrations/YYYY_MM_DD_HHMMSS_add_is_elevated_city_and_is_capital_to_provinces_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provinces', function (Blueprint $table) {
            $table->boolean('is_capital')->default(false)->after('geographic_level');
            $table->boolean('is_elevated_city')->default(false)->after('is_capital');
            $table->index('is_elevated_city');
            $table->index('is_capital');
        });
    }

    public function down(): void
    {
        Schema::table('provinces', function (Blueprint $table) {
            $table->dropIndex(['is_elevated_city']);
            $table->dropIndex(['is_capital']);
            $table->dropColumn(['is_elevated_city', 'is_capital']);
        });
    }
};
```

### 2. Model Changes

#### Update Province Model

```php
// app/Models/Province.php

class Province extends Model
{
    protected $fillable = [
        'code',
        'name',
        'correspondence_code',
        'geographic_level',
        'is_capital',
        'is_elevated_city',
        'psgc_version_id',
        'region_id',
    ];

    protected $casts = [
        'is_capital' => 'boolean',
        'is_elevated_city' => 'boolean',
    ];

    /**
     * Scope to get elevated NCR cities.
     */
    public function scopeElevatedCities($query)
    {
        return $query->where('is_elevated_city', true);
    }

    /**
     * Scope to get actual provinces (not elevated cities).
     */
    public function scopeActualProvinces($query)
    {
        return $query->where('is_elevated_city', false);
    }

    /**
     * Scope to get capital provinces/cities.
     */
    public function scopeCapital($query)
    {
        return $query->where('is_capital', true);
    }
}
```

### 3. Import Logic Changes

#### Modify `ImportPsgcData::processRow()` to Handle NCR

```php
protected function processRow(array $headers, array $rowData): void
{
    $data = array_combine($headers, $rowData);

    $code = $data['10-digit PSGC'] ?? '';
    $name = $data['Name'] ?? '';
    $correspondenceCode = $data['Correspondence Code'] ?? '';
    $geographicLevel = trim($data['Geographic Level'] ?? '');
    // Note: is_capital detection from Excel file is not working - implementation pending

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

    // Extract region prefix (first 2 digits)
    $regionPrefix = substr($code, 0, 2);

    // Check if this is NCR (Region 13)
    $isNCR = $regionPrefix === '13';

    // TODO: Implement is_capital detection from Excel data
    // $isCapital = $this->detectCapitalStatus($data);

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
            'is_capital' => false, // TODO: Implement detection
            'is_elevated_city' => false,
        ];
    } elseif (in_array($normalizedLevel, ['city', 'mun', 'municipality', 'submun'], true)) {
        // ELEVATE NCR cities/municipalities to provinces table
        if ($isNCR) {
            $this->provinces[$code] = [
                'code' => $code,
                'name' => $name,
                'correspondence_code' => $correspondenceCode,
                'geographic_level' => $mappedLevel, // Keep as 'City' or 'Municipality'
                'is_capital' => false, // TODO: Implement detection
                'is_elevated_city' => true, // Flag this as elevated
            ];
        } else {
            // Non-NCR cities go to cities_municipalities table
            $this->citiesMunicipalities[$code] = [
                'code' => $code,
                'name' => $name,
                'correspondence_code' => $correspondenceCode,
                'geographic_level' => $mappedLevel,
                'is_capital' => false, // TODO: Implement detection
            ];
        }
    } elseif ($normalizedLevel === 'bgy' || $normalizedLevel === 'barangay') {
        $this->barangays[$code] = [
            'code' => $code,
            'name' => $name,
            'correspondence_code' => $correspondenceCode,
            'geographic_level' => $mappedLevel,
        ];
    }
}
```

#### Modify `ImportPsgcData::establishRelationships()`

```php
protected function establishRelationships(): void
{
    // Establish province -> region relationships
    foreach ($this->provinces as $code => &$province) {
        $regionPrefix = substr($code, 0, 2);
        $regionCode = $regionPrefix . '00000000';

        if (isset($this->regions[$regionCode])) {
            $province['region_id'] = $this->regions[$regionCode]['id'] ?? null;
        }
    }

    // Establish city -> region and city -> province relationships
    foreach ($this->citiesMunicipalities as $code => &$cityMunicipality) {
        $regionPrefix = substr($code, 0, 2);
        $provincePrefix = substr($code, 0, 6);

        $regionCode = $regionPrefix . '00000000';
        $provinceCode = $provincePrefix . '0000';

        if (isset($this->regions[$regionCode])) {
            $cityMunicipality['region_id'] = $this->regions[$regionCode]['id'] ?? null;
        }

        if (isset($this->provinces[$provinceCode])) {
            $cityMunicipality['province_id'] = $this->provinces[$provinceCode]['id'] ?? null;
        }
    }
}
```

#### Modify `ImportPsgcData::saveProvinces()`

```php
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
                'is_capital' => $province['is_capital'] ?? false,
                'is_elevated_city' => $province['is_elevated_city'] ?? false,
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
```

#### Modify `ImportPsgcData::saveCitiesMunicipalities()`

```php
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
            'is_capital' => $cityMunicipality['is_capital'] ?? false,
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
```

### 4. Special Notes on region_id and province_id

#### For Elevated NCR Cities (stored in `provinces` table):

| Column      | Status | Value                                  |
|-------------|---------|----------------------------------------|
| `region_id` | **SET** | Points to NCR region (code `13`)        |
| `province_id` | **NOT APPLICABLE** | This column does not exist in `provinces` table |

#### For Non-NCR Cities (stored in `cities_municipalities` table):

| Column      | Status | Value                                  |
|-------------|---------|----------------------------------------|
| `region_id` | **SET** | Points to their parent region           |
| `province_id` | **SET** | Points to their parent province        |

#### For Barangays (stored in `barangays` table):

| Column                   | Status                                   | Value                                                        |
|--------------------------|------------------------------------------|-------------------------------------------------------------|
| `region_id`              | **SET**                                  | Points to parent region                                       |
| `province_id`             | **SET**                                  | Points to parent province (or elevated NCR city)               |
| `city_municipality_id`    | **NOT SET** for NCR barangays            | NULL when parent is elevated NCR city                         |
| `city_municipality_id`    | **SET** for non-NCR barangays           | Points to parent city/municipality                             |

### 5. Known Issues

#### Issue: is_capital Not Detected Upon Importation

**Status:** **Not Implemented - Pending Implementation**

**Description:**
The `is_capital` column on both `provinces` and `cities_municipalities` tables is currently not being populated during import. All records will have `is_capital` set to `false` by default.

**Impact:**
- Capital cities and municipalities cannot be identified from data
- Applications requiring capital information will not function correctly
- Administrative queries will be incomplete

**Root Cause:**
- The Excel file column containing capital status information needs to be identified
- The import logic in `ImportPsgcData::processRow()` needs to be updated to parse capital status from Excel data
- Detection logic for capital status has not been implemented yet

**Next Steps:**
1. Identify the Excel column that contains capital status information
2. Update the `processRow()` method to extract capital status from that column
3. Implement detection logic (likely checking for "Capital" keyword in column value)
4. Update test cases to verify capital detection
5. Re-import data to populate the `is_capital` field correctly

**Note:** This is a future implementation task. For now, all `is_capital` values will remain `false`.

### 6. Test Cases

#### Test Case 1: NCR City Elevation
- **Input:** NCR city with code `1375040000` (City of Manila)
- **Expected Behavior:**
  - Stored in `provinces` table
  - `is_elevated_city` = `true`
  - `is_capital` = `false` (see known issue)
  - `geographic_level` = `"City"`
  - `region_id` = NCR region ID
- **Verification:**
  ```php
  $manila = Province::where('code', '1375040000')->first();
  $this->assertTrue($manila->is_elevated_city);
  $this->assertEquals('City', $manila->geographic_level);
  // Note: is_capital test will fail until implementation is done
  // $this->assertTrue($manila->is_capital);
  ```

#### Test Case 2: Non-NCR Province
- **Input:** Ilocos Norte province with code `0128000000`
- **Expected Behavior:**
  - Stored in `provinces` table
  - `is_elevated_city` = `false`
  - `is_capital` = `false` (see known issue)
  - `geographic_level` = `"Province"`
  - `region_id` = Region I (01) ID
- **Verification:**
  ```php
  $ilocosNorte = Province::where('code', '0128000000')->first();
  $this->assertFalse($ilocosNorte->is_elevated_city);
  $this->assertEquals('Province', $ilocosNorte->geographic_level);
  ```

#### Test Case 3: Non-NCR City
- **Input:** Laoag City (code `0128010000`)
- **Expected Behavior:**
  - Stored in `cities_municipalities` table
  - `geographic_level` = `"City"`
  - `is_capital` = `false` (see known issue)
  - `region_id` = Region I (01) ID
  - `province_id` = Ilocos Norte province ID
- **Verification:**
  ```php
  $laoag = CityMunicipality::where('code', '0128010000')->first();
  $this->assertNotNull($laoag);
  $this->assertEquals('City', $laoag->geographic_level);
  $this->assertEquals('0128000000', $laoag->province->code);
  ```

#### Test Case 4: NCR Barangay
- **Input:** Barangay with code `1375040001` (Barangay 1, Zone 1, Manila)
- **Expected Behavior:**
  - Stored in `barangays` table
  - `region_id` = NCR region ID
  - `province_id` = Manila (elevated city) ID
  - `city_municipality_id` = NULL
- **Verification:**
  ```php
  $barangay = Barangay::where('code', '1375040001')->first();
  $this->assertNull($barangay->city_municipality_id);
  $this->assertNotNull($barangay->province);
  $this->assertTrue($barangay->province->is_elevated_city);
  ```

### 7. Migration Plan

#### Phase 1: Database Schema Update
1. Create migration to add `is_elevated_city` and `is_capital` columns to the provinces table
2. Run migration: `php artisan migrate`

#### Phase 2: Model Updates
1. Update the Province model with new fillable fields and scopes
2. Update the CityMunicipality model with `is_capital` in fillable (already exists)
3. Add casts for boolean fields

#### Phase 3: Import Logic Updates
1. Update `ImportPsgcData::processRow()` to detect NCR and elevate cities
2. Update `ImportPsgcData::saveProvinces()` to include new columns
3. Update `ImportPsgcData::saveCitiesMunicipalities()` to handle `is_capital`

#### Phase 4: Testing
1. Run PSGC sync with test data
2. Verify NCR cities are in the provinces table
3. Verify non-NCR cities are in the cities_municipalities table
4. Verify relationships are correctly established
5. Run automated tests

#### Phase 5: Capital Detection Implementation (Future)
1. Identify the Excel column with capital status
2. Implement `detectCapitalStatus()` helper method
3. Update `processRow()` to call helper and set `is_capital`
4. Test capital detection
5. Re-import data to populate the correct capital status

### 8. Backward Compatibility

This change is **backward compatible** for queries:

- Existing queries fetching from the `provinces` table will continue to work
- The new columns have default values of `false`
- Applications that don't need to distinguish can ignore these fields
- Applications that need to exclude NCR cities can use `actualProvinces()` scope

### 9. Performance Considerations

- The new columns have indexes for efficient filtering
- Querying `Province::actualProvinces()` and `Province::capital()` will use indexes
- No significant performance impact expected

### 10. Future Enhancements

- Implement capital status detection from Excel file
- Consider adding a computed/serialized column for "administrative level" that abstracts elevation logic
- Add helper methods to easily get all first-level administrative divisions (provinces + elevated cities)
- Consider API endpoints that return unified province/city lists

---

## Additional Resources

### Documentation
- **Spec File**: `.references/specs/ncr-cities-elevation-spec.md` (this file)
- **Implementation Guides**: `.claude/agents/apollo/`
- **Configuration**: `config/psgc.php`

### Related Files
- `app/Models/Province.php` - Province model with elevation fields
- `app/Models/CityMunicipality.php` - City/Municipality model
- `app/Actions/Psgc/ImportPsgcData.php` - Import logic
- `database/migrations/*` - Database migrations

### Support
For implementation assistance, refer to the Agent Apollo documentation at `.claude/agents/apollo`.

---

**Document Version:** 1.0
**Last Updated:** 2026-01-14
**Status:** Draft
**Author:** PSA-PSGC Team

## Changelog

### v1.0 (2026-01-14)
- Initial specification for NCR cities elevation
- Added `is_capital` known issue documentation
- Added references to Agent Apollo implementation guides
