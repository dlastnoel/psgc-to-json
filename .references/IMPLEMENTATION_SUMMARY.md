# PSGC Implementation Summary

## Date: 2026-01-14

---

## Overview

This document summarizes the implementation of the PSGC (Philippine Standard Geographic Code) data processing system, including version management, data import pipeline, and JSON API endpoints.

---

## Phase 1: Database Foundation & Version Management ✅

### PsgcVersion Model

Created a new `PsgcVersion` model to track PSGC publication versions:

**Table Schema:**
- `id` - Primary key
- `quarter` - Publication quarter (e.g., "4Q")
- `year` - Publication year (e.g., "2025")
- `publication_date` - Date of publication
- `download_url` - URL where the file was downloaded from
- `filename` - Original filename
- `is_current` - Boolean flag for active version
- `regions_count`, `provinces_count`, `cities_municipalities_count`, `barangays_count` - Record counts
- Indexes on `is_current` and `psgc_version_id`

**Key Methods:**
- `setCurrent()` - Sets this version as current, marks all others as not current
- `getCurrentVersion()` - Static method to retrieve current version instance
- `scopeCurrent()` - Query scope for current version
- Relationships to all geographic models

### Geographic Tables Updated

Added `psgc_version_id` foreign key to:
- `regions` table
- `provinces` table
- `cities_municipalities` table
- `barangays` table

All foreign keys are nullable with `nullOnDelete()` to handle version cleanup.

### Model Updates

All geographic models (`Region`, `Province`, `CityMunicipality`, `Barangay`) now include:

**Relationships:**
- `psgcVersion(): BelongsTo` - Links to PSGC version

**Scopes:**
- `scopeCurrent()` - Filters to current PSGC version
- `scopeVersion($id)` - Filters to specific version ID

**Fillable Fields:**
- Added `psgc_version_id` to all models

---

## Phase 2: Data Processing Pipeline ✅

### Refactored ImportPsgcData

Updated `ImportPsgcData` action to support version management:

**New Constructor Parameters:**
- `filePath` - Path to Excel file
- `filename` - Original filename (for version extraction)
- `downloadUrl` - URL where file was downloaded

**New Properties:**
- `$psgcVersion` - Stores the version instance
- `$quarter`, `$year` - Extracted version info

**New Methods:**
- `extractVersionInfo()` - Parses filename to extract quarter/year
- `createPsgcVersion()` - Creates new version record
- `updatePsgcVersionCounts()` - Updates version with record counts
- `markVersionAsCurrent()` - Sets new version as active

**Updated Methods:**
- `saveRegions()` - Now saves with `psgc_version_id`
- `saveProvinces()` - Now saves with `psgc_version_id`
- `saveCitiesMunicipalities()` - Now saves with `psgc_version_id`
- `saveBarangays()` - Now saves with `psgc_version_id`

**Key Changes:**
- `updateOrCreate()` now uses compound key (`code` + `psgc_version_id`)
- Each import creates a NEW version instead of overwriting existing data
- Old versions are preserved for historical queries

### Existing Actions (Unchanged)

- `CrawlPsgcWebsite` - Scrapes PSA website for latest download URL
- `DownloadPsgc` - Downloads Excel file from URL
- `ValidatePsgcExcel` - Validates Excel structure with row limit filter

---

## Phase 3: API Layer & Sync Command ✅

### API Resources

Created Eloquent API Resources for JSON transformation:

**RegionResource:**
- Returns: `id`, `code`, `name`, `correspondence_code`, `geographic_level`, `psgc_version_id`
- Includes: `provinces` collection (when loaded)

**ProvinceResource:**
- Returns: All Province fields + `region_id`, `psgc_version_id`
- Includes: `cities_municipalities` collection (when loaded)

**CityMunicipalityResource:**
- Returns: All City/Municipality fields + `region_id`, `province_id`, `is_capital`, `psgc_version_id`
- Includes: `barangays` collection (when loaded)

**BarangayResource:**
- Returns: All Barangay fields + `region_id`, `province_id`, `city_municipality_id`, `psgc_version_id`
- No nested relationships

### API Controllers

Created RESTful controllers following Cruddy-by-Design pattern:

**RegionController:**
- `index()` - List all regions (with provinces)
- `show()` - Show single region (with full hierarchy)
- Supports `?version={id}` parameter
- Defaults to current version if no version specified

**ProvinceController:**
- `index()` - List all provinces (with cities)
- `show()` - Show single province (with full hierarchy)
- Supports `?version={id}` and `?region_id={id}` parameters
- Defaults to current version

**CityMunicipalityController:**
- `index()` - List all cities/municipalities (with barangays)
- `show()` - Show single city/municipality (with full hierarchy)
- Supports `?version={id}`, `?region_id={id}`, `?province_id={id}` parameters
- Defaults to current version

**BarangayController:**
- `index()` - List all barangays
- `show()` - Show single barangay (with parent hierarchy)
- Supports `?version={id}`, `?region_id={id}`, `?province_id={id}`, `?city_municipality_id={id}` parameters
- Defaults to current version

### API Routes

Created `routes/api.php` with PSGC-specific routes:

```
/api/psgc/regions              - GET list, GET show
/api/psgc/regions/{region}    - GET show
/api/psgc/provinces            - GET list, GET show
/api/psgc/provinces/{province}  - GET show
/api/psgc/cities-municipalities      - GET list
/api/psgc/cities-municipalities/{city} - GET show
/api/psgc/barangays           - GET list, GET show
/api/psgc/barangays/{barangay} - GET show
```

All routes:
- Only allow `index` and `show` (no CRUD operations needed)
- Support version filtering via query parameters
- Return JSON responses via API Resources

### SyncCommand Updates

Updated `psgc:sync` command to pass version metadata:

**Changes:**
- `downloadPhase()` now returns array with `path`, `filename`, `download_url`
- `importPhase()` accepts download result array
- `displayImportSummary()` shows PSGC version ID
- Filename and URL are preserved for version tracking

**Exit Codes:**
- `0` - Success
- `1` - Validation failed
- `2` - Download failed
- `3` - Import failed

### SyncPsgcJob Updates

Updated queued job to match new command signature:

**Changes:**
- `downloadPhase()` returns array with filename and download URL
- `importPhase()` accepts download result array
- Properly tracks version metadata in logs

---

## Removed Components

### FileCache Class

The `FileCache` class was removed as it was:
- Created but never used in the codebase
- Not required for current implementation
- Can be re-added later if caching is needed

---

## Usage Examples

### Syncing PSGC Data

**Automatic Download:**
```bash
php artisan psgc:sync
```
This will:
1. Crawl PSA website for latest publication
2. Download Excel file
3. Validate structure
4. Import as new version
5. Set as current version

**Manual File:**
```bash
php artisan psgc:sync --path=/path/to/PSGC-4Q-2025-Publication-Datafile.xlsx
```
This will use the provided file instead of downloading.

**Queue Mode:**
```bash
php artisan psgc:sync --queue
```
Dispatches job to background queue for large file processing.

### Querying Current Version

**Via Eloquent:**
```php
$regions = Region::current()->with('provinces')->get();
$provinces = Province::current()->where('region_id', 1)->get();
```

**Via API:**
```bash
# Current regions (default)
GET /api/psgc/regions

# Current provinces filtered by region
GET /api/psgc/provinces?region_id=1

# Current barangays filtered by city
GET /api/psgc/barangays?city_municipality_id=123
```

### Querying Historical Version

**Via Eloquent:**
```php
// Get data from version 5
$regions = Region::version(5)->get();
$barangays = Barangay::version(5)->where('province_id', 2)->get();
```

**Via API:**
```bash
# Regions from version 3
GET /api/psgc/regions?version=3

# Provinces from version 3, filtered by region
GET /api/psgc/provinces?version=3&region_id=1
```

### Managing Versions

**Get Current Version:**
```php
$currentVersion = PsgcVersion::getCurrentVersion();
echo "Current: {$currentVersion->quarter} {$currentVersion->year}";
```

**List All Versions:**
```php
$versions = PsgcVersion::orderBy('created_at', 'desc')->get();
foreach ($versions as $version) {
    echo "{$version->quarter} {$version->year} - ";
    echo ($version->is_current ? 'Current' : 'Historical') . "\n";
}
```

**Set Specific Version as Current:**
```php
$version = PsgcVersion::find(5);
$version->setCurrent();
```

---

## Data Flow Diagram

```
┌─────────────────┐
│ PSA Website   │
└───────┬───────┘
        │
        ▼
┌─────────────────────┐
│ CrawlPsgcWebsite │ → Download URL
└───────┬──────────┘
        │
        ▼
┌──────────────────┐
│ DownloadPsgc    │ → Excel File
└───────┬────────┘
        │
        ▼
┌────────────────────┐
│ ValidatePsgcExcel│ → Validation Result
└───────┬──────────┘
        │
        ▼
┌────────────────────────┐
│ ImportPsgcData      │
├────────────────────────┤
│ • Parse Excel        │
│ • Extract Version   │
│ • Create Version    │ → PsgcVersion
│ • Import Regions   │
│ • Import Provinces │
│ • Import Cities    │
│ • Import Barangays │
│ • Set Current      │
└───────┬────────────┘
        │
        ▼
┌─────────────────────────────────────────────┐
│ Database Tables (with psgc_version_id) │
│ • psgc_versions                      │
│ • regions                            │
│ • provinces                          │
│ • cities_municipalities               │
│ • barangays                          │
└──────────────────┬──────────────────────┘
                   │
                   ▼
         ┌─────────────────────────────┐
         │ API Controllers & Resources│
         ├─────────────────────────────┤
         │ • RegionController         │
         │ • ProvinceController       │
         │ • CityMunicipalityController│
         │ • BarangayController      │
         └─────────────────────┬──────────┘
                              │
                              ▼
                    ┌──────────────────────┐
                    │ JSON API Endpoints   │
                    │ /api/psgc/*        │
                    └──────────────────────┘
```

---

## Testing Status

### Implemented Tests

**Existing Tests (Need Updates):**
- `tests/Feature/Psgc/QueueModeTest.php` - Basic queue dispatch tests
- `tests/Feature/Psgc/SyncCommandTest.php` - Basic sync command tests
- `tests/Feature/Psgc/SyncCommandEnhancedTest.php` - Enhanced sync tests

### Missing Tests (TODO)

**Unit Tests:**
- PsgcVersion model tests
  - `setCurrent()` marks version as current
  - `getCurrentVersion()` returns current version
  - `scopeCurrent()` filters correctly
  - `scopeVersion($id)` filters correctly

- ImportPsgcData action tests
  - Creates new PsgcVersion
  - Extracts quarter/year from filename
  - Updates version counts
  - Imports with correct version linkage

**Feature Tests:**
- API endpoint tests
  - GET /api/psgc/regions returns current data
  - GET /api/psgc/regions?version=3 returns historical data
  - GET /api/psgc/provinces?region_id=1 filters correctly
  - Relationships are loaded correctly

- Model scope tests
  - Region::current() returns only current version
  - Region::version(5) returns only version 5
  - Version filtering works across all levels

---

## Future Enhancements

### Phase 4: Enhanced Features (Not Yet Implemented)

**Historical API Endpoints:**
- `GET /api/psgc/versions` - List all versions
- `GET /api/psgc/versions/{id}` - Get version details
- `GET /api/psgc/versions/diff/{from}/{to}` - Compare versions

**Search API:**
- `GET /api/psgc/search?q=Tondo` - Global search across all levels

**Change Tracking:**
- Track additions/deletions between versions
- Show detailed change logs

### Phase 5: Polish & Admin (Not Yet Implemented)

**API Caching:**
- Redis cache for frequently accessed endpoints
- Cache invalidation on sync

**Admin Dashboard:**
- List and manage PSGC versions
- View version statistics
- Download original Excel files

**Rollback Command:**
- `php artisan psgc:rollback {version_id}` - Set previous version as current

---

## Configuration Files

### config/psgc.php

Current configuration:
```php
'psa_url' => env('PSGC_PSA_URL', 'https://psa.gov.ph/classification/psgc'),
'storage_path' => env('PSGC_STORAGE_PATH', 'psgc'),
'memory_limit' => env('PSGC_MEMORY_LIMIT', 1024), // MB
'filename_pattern' => '/^PSGC-(\dQ)-(\d{4})-Publication-Datafile\.xlsx$/i',
'validation' => [
    'required_sheet' => 'PSGC',
    'required_columns' => [...],
    'valid_geographic_levels' => ['Region', 'Province', 'City', 'Municipality', 'Barangay', 'SubMun'],
    'district_level' => 'SubMun',
],
```

---

## Design Pattern Adherence

### Followed Patterns

✅ **No Repository Layer** - Direct Eloquent access in controllers
✅ **Action Pattern** - Complex logic in actions (`ImportPsgcData`, etc.)
✅ **Cruddy-by-Design** - Controllers only implement index/show
✅ **Eloquent API Resources** - JSON transformation layer
✅ **Version Scopes** - `current()` and `version($id)` on models
✅ **Full Data Per Version** - Each version has complete data copies

### Naming Conventions

✅ **Controllers** - Singular (`RegionController`, `ProvinceController`)
✅ **Models** - Singular (`Region`, `Province`, `CityMunicipality`, `Barangay`)
✅ **API Resources** - Singular (`RegionResource`, `ProvinceResource`)
✅ **Actions** - Verb-based (`ImportPsgcData`, `CrawlPsgcWebsite`)
✅ **Commands** - Verb-based (`SyncCommand`)
✅ **Database** - Plural snake case (`psgc_versions`, `regions`)

---

## Migration Files

### New Migrations Created:

1. `create_psgc_versions_table` - Creates version tracking table
2. `add_psgc_version_id_to_regions_table` - Adds foreign key
3. `add_psgc_version_id_to_provinces_table` - Adds foreign key
4. `add_psgc_version_id_to_cities_municipalities_table` - Adds foreign key
5. `add_psgc_version_id_to_barangays_table` - Adds foreign key

### Note on Migration Order

These migrations should be run in order:
1. Create `psgc_versions` table first
2. Add foreign keys to existing tables

Foreign keys use `nullable()` and `nullOnDelete()` to handle:
- Existing records without versions (created before versioning)
- Clean deletion of versions without cascading deletes

---

## Performance Considerations

### Database Indexes

Added indexes for:
- `is_current` on `psgc_versions` - Fast current version lookups
- `psgc_version_id` on all geographic tables - Fast version filtering
- Existing indexes on `code` columns remain for code-based lookups

### Memory Management

- Queue job uses 1GB memory limit (configurable)
- Validation uses row limit filter (100 rows) to reduce memory
- Large Excel files handled via queued jobs

### Query Optimization

- `whenLoaded()` in API Resources prevents N+1 queries
- Eager loading of relationships in controllers
- Version filtering at database level (not in PHP)

---

## Known Limitations

### Current Implementation

1. **No API Authentication** - Endpoints are public
2. **No Pagination** - Returns all records (suitable for PSGC size)
3. **No Rate Limiting** - Unlimited API access
4. **No Search** - Only filter by parent IDs
5. **No Diff Tracking** - Can't see changes between versions
6. **No Rollback Command** - Must manually set version as current
7. **Tests Incomplete** - Many tests need updating or creation

### Acceptable for MVP

These limitations are acceptable for:
- MVP release
- Internal use cases
- Controlled API consumers

Should be addressed in Phase 4/5 enhancements.

---

## Deployment Checklist

Before deploying to production:

- [ ] Run all migrations (`php artisan migrate`)
- [ ] Test sync command with real PSA file
- [ ] Verify API endpoints return correct data
- [ ] Check current version queries work
- [ ] Check historical version queries work
- [ ] Set up queue worker (`php artisan queue:work`)
- [ ] Configure appropriate memory limit
- [ ] Set up PSA URL in environment
- [ ] Test error handling (download failures, validation errors)
- [ ] Update application documentation
- [ ] Add API rate limiting if needed for public access

---

## Conclusion

This implementation successfully completes **Phase 1** (Database Foundation & Version Management) and **Phase 3** (API Layer & Sync Command) of the roadmap. The data processing pipeline from **Phase 2** has been enhanced with version management capabilities.

The system now:
- ✅ Tracks PSGC publication versions
- ✅ Preserves historical data across versions
- ✅ Provides JSON API endpoints with version filtering
- ✅ Supports manual and automatic sync workflows
- ✅ Follows agreed-upon design patterns

Next steps should focus on:
- Completing test coverage
- Adding Phase 4 features (historical queries, diff tracking)
- Adding Phase 5 polish (caching, admin dashboard, rollback command)
