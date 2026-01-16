# Code Columns Implementation Summary

## Overview
Added region_code, province_code, and city_municipality_code columns to PSGC tables for reference and debugging purposes.

## Migrations Created

### 1. Provinces Table
**File**: `2026_01_15_044434_add_region_code_province_code_to_provinces_table.php`

**Columns Added**:
- `region_code` (string, 10 chars) - After `region_id`
- `province_code` (string, 10 chars) - After `region_code`

**Purpose**:
- `region_code`: Stores the 10-digit PSGC code of the parent region (e.g., `0100000000` for Region I)
- `province_code`: Stores the province's own 10-digit PSGC code (same as `code` column, for reference)

### 2. Cities/Municipalities Table
**File**: `2026_01_15_044545_add_region_code_province_code_to_cities_municipalities_table.php`

**Columns Added**:
- `region_code` (string, 10 chars) - After `region_id`
- `province_code` (string, 10 chars) - After `province_id`

**Purpose**:
- `region_code`: Stores the 10-digit PSGC code of the parent region
- `province_code`: Stores the 10-digit PSGC code of the parent province

### 3. Barangays Table
**File**: `2026_01_15_044636_add_region_code_province_code_city_municipality_code_to_barangays_table.php`

**Columns Added**:
- `region_code` (string, 10 chars) - After `region_id`
- `province_code` (string, 10 chars) - After `province_id`
- `city_municipality_code` (string, 10 chars) - After `city_municipality_id`

**Purpose**:
- `region_code`: Stores the 10-digit PSGC code of the parent region
- `province_code`: Stores the 10-digit PSGC code of the parent province
- `city_municipality_code`: Stores the 10-digit PSGC code of the parent city/municipality

## Implementation Details

### Import Logic Updated

#### 1. Provinces (saveProvinces)
```php
$regionCode = substr($code, 0, 2) . '00000000'; // Region code from province code

Province::updateOrCreate(
    ['code' => $code, ...],
    [
        ...
        'region_id' => $province['region_id'],
        'region_code' => $regionCode,      // NEW: Parent region code
        'province_code' => $code,           // NEW: Province's own code
        ...
    ]
);
```

#### 2. Cities/Municipalities (saveCitiesMunicipalities)
```php
$regionCode = substr($code, 0, 2) . '00000000'; // Region code from city code

// Find province code reference by matching province ID
foreach ($this->provinces as $provCode => $prov) {
    if ($prov['id'] === $provinceId) {
        $provinceCodeRef = $provCode;
        break;
    }
}

CityMunicipality::updateOrCreate(
    ['code' => $code, ...],
    [
        ...
        'region_id' => $cityMunicipality['region_id'],
        'region_code' => $regionCode,          // NEW: Parent region code
        'province_id' => $provinceId,
        'province_code' => $provinceCodeRef,     // NEW: Parent province code
        ...
    ]
);
```

#### 3. Barangays (saveBarangays)
```php
// Find region code reference by matching region ID
foreach ($this->regions as $regCode => $reg) {
    if ($reg['id'] === $barangay['region_id']) {
        $data['region_code'] = $regCode;
        break;
    }
}

// Find province code reference by matching province ID
foreach ($this->provinces as $provCode => $prov) {
    if ($prov['id'] === $barangay['province_id']) {
        $data['province_code'] = $provCode;
        break;
    }
}

// Find city_municipality code reference by matching city ID
foreach ($this->citiesMunicipalities as $cityCode => $city) {
    if ($city['id'] === $barangay['city_municipality_id']) {
        $data['city_municipality_code'] = $cityCode;
        break;
    }
}

Barangay::updateOrCreate(
    ['code' => $code, ...],
    [
        ...
        'region_id' => $barangay['region_id'],
        'region_code' => $barangay['region_code'],           // NEW: Parent region code
        'province_id' => $barangay['province_id'],
        'province_code' => $barangay['province_code'],         // NEW: Parent province code
        'city_municipality_id' => $barangay['city_municipality_id'],
        'city_municipality_code' => $barangay['city_municipality_code'], // NEW: Parent city/municipality code
        ...
    ]
);
```

## Test Results

All tests passed successfully:

```
Total Regions: 2
Total Provinces: 4
Total Cities/Municipalities: 3
Total Barangays: 4

All new code columns populated correctly.
```

## Benefits

1. **Reference**: Easy to see parent codes without querying related tables
2. **Debugging**: Quick verification of relationships during development
3. **Performance**: No additional queries needed to fetch parent codes
4. **Data Integrity**: Double-check relationship validity using both IDs and codes

## Usage Examples

```sql
-- Get Ilocos Norte with both region and province codes
SELECT code, name, region_code, province_code
FROM provinces
WHERE code = '0128000000';

-- Result: code='0128000000', name='Ilocos Norte', region_code='0100000000', province_code='0128000000'
```

```sql
-- Get barangay with all parent codes
SELECT
    b.code AS barangay_code,
    b.name AS barangay_name,
    b.region_code,
    b.province_code,
    b.city_municipality_code,
    p.name AS province_name,
    c.name AS city_name
FROM barangays b
LEFT JOIN provinces p ON b.province_id = p.id
LEFT JOIN cities_municipalities c ON b.city_municipality_id = c.id
WHERE b.code = '1375040001';
```

## Notes

- All code columns are nullable (NULL) to handle edge cases
- Code columns store full 10-digit PSGC codes
- Foreign key IDs still used for actual relationships
- Code columns are for reference/validation only
