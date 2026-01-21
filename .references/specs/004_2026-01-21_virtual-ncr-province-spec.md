# Virtual NCR Province Specification

## Overview

All cities/municipalities under the National Capital Region (NCR) must be stored in the `cities_municipalities` table with a **virtual province** as their parent. This approach maintains a consistent data structure without elevating NCR cities to the `provinces` table.

## Background

The Philippine Standard Geographic Code (PSGC) structure has a special case for the National Capital Region (NCR - Region Code: `13`). NCR does not have traditional provinces; instead, cities/municipalities are directly under NCR.

**Previous Approach (Deprecated):** NCR cities were elevated to the `provinces` table with an `is_elevated_city` flag.

**New Approach:** A single virtual province is created for NCR (code: `1300000000`, `is_virtual: true`), and all NCR cities/municipalities are stored in the `cities_municipalities` table with this virtual province as their parent.

## Database Schema

### New Column in `provinces` Table

```sql
ALTER TABLE provinces ADD COLUMN is_virtual BOOLEAN DEFAULT FALSE;
CREATE INDEX idx_provinces_is_virtual ON provinces(is_virtual);
```

### Virtual Province Record for NCR

| Column | Value |
|--------|-------|
| `code` | `1300000000` |
| `name` | `Metro Manila` |
| `geographic_level` | `Province` |
| `is_virtual` | `true` |
| `is_elevated_city` | `false` |
| `region_id` | Points to NCR region (code `13`) |

## Implementation Guides

### 1. Model Changes

#### Province Model

```php
// app/Models/Province.php

class Province extends Model
{
    protected $fillable = [
        // ... existing fields ...
        'is_virtual',
    ];

    protected $casts = [
        // ... existing casts ...
        'is_virtual' => 'boolean',
    ];

    /**
     * Scope to get virtual provinces (e.g., NCR).
     */
    public function scopeVirtual($query)
    {
        return $query->where('is_virtual', true);
    }

    /**
     * Scope to get real provinces only (not virtual).
     */
    public function scopeRealProvinces($query)
    {
        return $query->where('is_virtual', false);
    }
}
```

### 2. Import Logic Changes

#### Create Virtual Province for NCR

When processing the NCR region during import, automatically create the virtual province:

```php
protected function processRow(array $headers, array $rowData): void
{
    // ... parsing logic ...

    if ($normalizedLevel === 'reg' || $normalizedLevel === 'region') {
        $this->regions[$code] = [
            'code' => $code,
            'name' => $name,
            'correspondence_code' => $correspondenceCode,
            'geographic_level' => $mappedLevel,
        ];

        // Create a virtual province for NCR when we encounter NCR region
        $regionPrefix = substr($code, 0, 2);
        if ($regionPrefix === '13') { // NCR region code
            $virtualProvinceCode = '1300000000';
            $this->provinces[$virtualProvinceCode] = [
                'code' => $virtualProvinceCode,
                'name' => 'Metro Manila',
                'correspondence_code' => $correspondenceCode,
                'geographic_level' => 'Province',
                'is_elevated_city' => false,
                'is_virtual' => true,
            ];
        }
    }
}
```

#### All NCR Cities Go to `cities_municipalities` Table

```php
protected function saveCitiesMunicipalities(): void
{
    foreach ($this->citiesMunicipalities as $code => &$cityMunicipality) {
        $isNCR = substr($code, 0, 2) === '13';

        if ($isNCR) {
            // Use virtual NCR province
            $virtualProvinceCode = '1300000000';
            if (isset($this->provinces[$virtualProvinceCode])) {
                $provinceId = $this->provinces[$virtualProvinceCode]['id'];
                $provinceCode = $virtualProvinceCode;
            }
        } else {
            // Non-NCR logic (traditional province or elevated city)
            // ...
        }

        // Save to cities_municipalities table
        CityMunicipality::updateOrCreate(
            ['code' => $code, 'psgc_version_id' => $this->psgcVersion->id],
            [
                'province_id' => $provinceId,
                'province_code' => $provinceCode,
                // ... other fields
            ]
        );
    }
}
```

## Data Structure Comparison

### Previous (Elevated Cities Approach)
- NCR cities stored in `provinces` table with `is_elevated_city = true`
- Mixed data in `provinces` table (real provinces + NCR cities)

### New (Virtual Province Approach)
- All NCR cities stored in `cities_municipalities` table
- Single virtual province record in `provinces` table
- Clean separation: `provinces` table only contains provinces (real + virtual)

## Relationship Structure

### NCR Cities/Municipalities
| Column | Value |
|--------|-------|
| `region_id` | Points to NCR region (code `13`) |
| `province_id` | Points to virtual NCR province (code `1300000000`) |
| `province_code` | `1300000000` |

### NCR Barangays
| Column | Value |
|--------|-------|
| `region_id` | Points to NCR region |
| `province_id` | Points to virtual NCR province |
| `city_municipality_id` | Points to parent city/municipality |

### Non-NCR Elevated Cities (HUC/ICC)
Non-NCR elevated cities (e.g., Cebu City) still use the `is_elevated_city` flag and exist in both tables:
- In `provinces` table with `is_elevated_city = true`
- In `cities_municipalities` table referencing themselves as province

## Test Cases

### Test Case 1: Virtual NCR Province Creation
- **Input:** NCR region with code `1300000000`
- **Expected Behavior:**
  - Virtual province created in `provinces` table
  - `is_virtual` = `true`
  - `code` = `1300000000`
- **Verification:**
  ```php
  $ncrVirtual = Province::where('code', '1300000000')->first();
  $this->assertTrue($ncrVirtual->is_virtual);
  $this->assertEquals('Metro Manila', $ncrVirtual->name);
  ```

### Test Case 2: NCR City Storage
- **Input:** City of Manila with code `1375040000`
- **Expected Behavior:**
  - Stored in `cities_municipalities` table (NOT in `provinces`)
  - `province_id` points to virtual NCR province
  - `province_code` = `1300000000`
- **Verification:**
  ```php
  $manila = CityMunicipality::where('code', '1375040000')->first();
  $this->assertNotNull($manila);
  $this->assertEquals('1300000000', $manila->province_code);
  $this->assertTrue($manila->province->is_virtual);
  ```

### Test Case 3: NCR Barangay Relationships
- **Input:** Barangay in Manila with code `1375040001`
- **Expected Behavior:**
  - Stored in `barangays` table
  - `province_id` points to virtual NCR province
  - `city_municipality_id` points to Manila
- **Verification:**
  ```php
  $barangay = Barangay::where('code', '1375040001')->first();
  $this->assertEquals('1300000000', $barangay->province_code);
  $this->assertNotNull($barangay->city_municipality_id);
  ```

### Test Case 4: Non-NCR Elevated City (Unchanged)
- **Input:** Cebu City with code `0722040000`
- **Expected Behavior:**
  - Stored in BOTH `provinces` and `cities_municipalities` tables
  - `is_elevated_city` = `true` in `provinces` table
- **Verification:**
  ```php
  $cebuAsProvince = Province::where('code', '0722040000')->first();
  $this->assertTrue($cebuAsProvince->is_elevated_city);
  $this->assertFalse($cebuAsProvince->is_virtual);

  $cebuAsCity = CityMunicipality::where('code', '0722040000')->first();
  $this->assertNotNull($cebuAsCity);
  ```

## Migration Plan

### Phase 1: Database Schema Update
1. Create migration to add `is_virtual` column to `provinces` table
2. Run migration: `php artisan migrate`

### Phase 2: Model Updates
1. Update Province model with `is_virtual` in fillable and casts
2. Add `scopeVirtual()` and `scopeRealProvinces()` scopes

### Phase 3: Import Logic Updates
1. Update `ImportPsgcData::processRow()` to create virtual NCR province
2. Update `ImportPsgcData::saveProvinces()` to handle `is_virtual`
3. Update `ImportPsgcData::saveCitiesMunicipalities()` to use virtual province for NCR
4. Remove logic that elevated NCR cities to provinces table

### Phase 4: Testing
1. Run PSGC sync with test data
2. Verify virtual NCR province exists
3. Verify all NCR cities are in `cities_municipalities` table
4. Verify NCR cities point to virtual province
5. Verify non-NCR elevated cities still work correctly
6. Run automated tests

## Backward Compatibility

- Existing queries using `Province::all()` will include virtual provinces
- To exclude virtual provinces, use `Province::realProvinces()` scope
- The `is_elevated_city` column is kept for non-NCR elevated cities (HUC/ICC outside NCR)

## Advantages Over Previous Approach

1. **Cleaner Data Model:** All cities/municipalities are in one table
2. **Consistent Relationships:** All cities have a province reference (real or virtual)
3. **Simpler Queries:** No need to check if a province is actually a city
4. **Easier Filtering:** Use `is_virtual` flag to exclude/include as needed

---

**Document Version:** 1.0
**Last Updated:** 2026-01-21
**Status:** Draft
**Supersedes:** `003_2026-01-14_ncr-cities-elevation-spec.md`

## Changelog

### v1.0 (2026-01-21)
- Initial specification for virtual NCR province approach
- Replaces NCR cities elevation approach
- Adds `is_virtual` column to provinces table
- Updates import logic to create virtual NCR province
- All NCR cities/municipalities now stored in cities_municipalities table
