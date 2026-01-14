# NCR Cities Elevation Specification

## Overview

All cities/municipalities under the National Capital Region (NCR) must be elevated to the **provinces** table while maintaining their geographic level as "City".

## Background

The Philippine Standard Geographic Code (PSGC) structure has a special case for the National Capital Region (NCR - Region Code: `13`). NCR does not have traditional provinces; instead, cities/municipalities are directly under NCR.

To maintain a consistent data structure, NCR cities should be stored in the `provinces` table while preserving their actual geographic level.

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

### 4. Known Issues

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
- The import logic in `ImportPsgcData::processRow()` needs to be updated to parse capital status from the Excel data
- Detection logic for capital status has not been implemented yet

**Next Steps:**
1. Identify the Excel column that contains capital status information
2. Update the `processRow()` method to extract capital status from that column
3. Implement detection logic (likely checking for "Capital" keyword in the column value)
4. Update test cases to verify capital detection
5. Re-import data to populate the `is_capital` field correctly

**Note:** This is a future implementation task. For now, all `is_capital` values will remain `false`.

---

**Document Version:** 1.0
**Last Updated:** 2026-01-14
**Status:** Draft
**Author:** PSA-PSGC Team
