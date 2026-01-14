# Project Roadmap

## PSA PSGC Data Processor

---

## Phased Roadmap

| Phase | Focus Area | Key Features | Success Criteria | Estimated Scope |
|-------|-----------|--------------|------------------|----------------|
| **Phase 1** | **Database Foundation & Version Management** | • Database schema design<br>• PSGC Version model<br>• Region, Province, Municipality, Barangay models with versioning<br>• Migration files | Database schema supports versioning; Models allow querying by version; Migration runs without errors | Medium |
| **Phase 2** | **Data Processing Pipeline** | • PSA Crawler (download Excel)<br>• Excel Parser (convert to structured data)<br>• Data Normalizer (handle Manila districts)<br>• Data Import Process with versioning | Can download and parse latest PSGC Excel; Data imports correctly with version tracking | Large |
| **Phase 3** | **API Layer & Sync Command** | • JSON API endpoints (current data)<br>• Artisan `psgc:sync` command<br>• Basic filtering support | API returns valid PSGC data; Sync command successfully updates database with new version | Medium |
| **Phase 4** | **Enhanced Features** | • Historical API endpoints<br>• Change/Diff tracking<br>• Search API<br>• Filtered queries | Can query specific PSGC versions; See what changed between versions | Medium |
| **Phase 5** | **Polish & Admin** | • API caching layer<br>• Basic admin dashboard<br>• Data validation<br>• Rollback command | Dashboard shows versions and changes; Caching improves performance; Rollback works | Medium |

---

## Feature Inventory

### Core/MVP Features (Essential)

| Feature | User Need | Importance | Dependencies |
|---------|-----------|------------|--------------|
| **1. PSA Crawler** | Download latest PSGC Excel from website automatically | Critical | None |
| **2. Excel Parser** | Convert Excel data to structured format (handle multiple sheets, columns) | Critical | #1 |
| **3. Data Normalizer** | Handle special cases (Manila districts → municipalities) | Critical | #2 |
| **4. Database Models** | Store Region, Province, Municipality, Barangay with versioning | Critical | None |
| **5. Version Management System** | Track PSGC versions and apply changes without losing old data | Critical | #4 |
| **6. Data Import Process** | Import normalized data into database with proper versioning | Critical | #3, #5 |
| **7. JSON API Endpoints (Current)** | Fetch current PSGC data for dropdowns | Critical | #6 |
| **8. Artisan Sync Command** | `php artisan psgc:sync` to update to latest dataset | Critical | #1-#6 |

---

### Enhanced Features (Important but not critical for launch)

| Feature | User Need | Importance | Dependencies |
|---------|-----------|------------|--------------|
| **9. Historical API Endpoints** | Fetch data from specific PSGC versions (e.g., NCR as of 2024-01) | High | #5 |
| **10. Change/Diff Tracking** | Show what changed between PSGC versions (new barangays, renamed municipalities) | High | #5 |
| **11. Search API** | Search by name across all levels (e.g., "Tondo" → returns Tondo, Manila, NCR) | High | #7 |
| **12. Filtered Queries API** | Filter by region, province, municipality, etc. | High | #7 |
| **13. API Caching Layer** | Improve performance for frequent queries | Medium | #7 |
| **14. Basic Admin Dashboard** | View and manage PSGC versions | Medium | #5 |
| **15. Data Validation** | Validate imported data against PSGC rules (e.g., ensure codes are unique) | Medium | #6 |
| **16. Rollback Command** | Revert to previous PSGC version if needed | Medium | #5, #6 |

---

### Future/Nice-to-Have (Can be deferred)

| Feature | User Need | Importance | Dependencies |
|---------|-----------|------------|--------------|
| **17. Webhook Support** | Notify external systems when PSGC updates | Low | #8 |
| **18. Export Functionality** | Export data to CSV, JSON, SQL formats | Low | #6 |
| **19. Legacy Import** | Import from other PSGC solutions (johnreybacal, EdeesonOpina) | Low | None |
| **20. API Rate Limiting** | Control API usage | Low | #7 |
| **21. Analytics Dashboard** | Track API usage, popular locations | Low | None |
| **22. Bulk Operations** | Bulk import/export operations | Low | None |
| **23. Auto-Detect Updates** | Automatically detect when PSA releases new PSGC | Low | #1 |
| **24. Change Notifications** | Email/Slack notifications for PSGC updates | Low | #17 |
| **25. Data Quality Reports** | Identify anomalies in PSGC data | Low | #6 |

---

## User Personas

### Persona 1: Alex (Junior Developer / IT Student)

**Background:** 20-year-old IT student, building first real application

**Key Characteristics:**
- Comfortable with basic web development but new to complex data structures
- Values clear documentation and examples
- Limited time - needs simple, drop-in solution

**Primary Goals:**
- Add location dropdowns to student registration form
- Focus on core app functionality, not data sourcing
- Get something working quickly

**Pain Points:**
- Found multiple GitHub packages but they're outdated or confusing
- Doesn't want to manually parse Excel files
- Overwhelmed by PSGC data complexity (especially Metro Manila)

**How They'll Use This App:**
- Install as Laravel package or consume JSON API endpoints
- Use `/api/regions`, `/api/provinces/{region}` endpoints for dropdowns
- Follow documentation examples

---

### Persona 2: Sarah (Full-Stack Laravel Developer)

**Background:** 28-year-old senior developer at a mid-sized tech company

**Key Characteristics:**
- Values code quality and maintainability
- Prefers clean, well-structured solutions
- Needs reliable data for production applications

**Primary Goals:**
- Integrate location selection into user onboarding flow
- Ensure data stays current without manual intervention
- Build admin features that reference location data

**Pain Points:**
- Existing solutions are outdated or poorly maintained
- Inconsistent data structures across different projects
- Has manually updated PSGC data before (tedious, error-prone)

**How She'll Use This App:**
- Run artisan commands to sync latest PSGC data
- Use Eloquent models: `Region::with('provinces')->get()`
- Create custom endpoints as needed

---

### Persona 3: Marco (Backend API Developer)

**Background:** 35-year-old backend engineer at a startup

**Key Characteristics:**
- Language-agnostic, focuses on data quality and API design
- Manages multiple services that need consistent location data
- Prioritizes reliability and performance

**Primary Goals:**
- Single source of truth for location data across all services
- Easy integration via clean JSON API
- Automated data updates without downtime

**Pain Points:**
- Different apps using different PSGC data sources (inconsistent!)
- Legacy systems with hardcoded location arrays (impossible to update)
- No unified implementation across the organization

**How He'll Use This App:**
- Consume JSON API from Laravel application
- Cache responses in Redis for performance
- Schedule weekly sync via cron job

---

### Persona 4: Elena (Enterprise Developer)

**Background:** 40-year-old software architect at a large government organization

**Key Characteristics:**
- Focuses on long-term maintainability and standardization
- Manages complex system migrations
- Needs auditable data sources and updates

**Primary Goals:**
- Standardize location data across 20+ legacy applications
- Replace hardcoded PSGC arrays with unified database
- Ensure data accuracy for government reporting

**Pain Points:**
- Each application has different PSGC implementation (chaos!)
- Some use outdated GitHub packages, others manual databases
- No process for updating data when PSGC releases new publications
- Data inconsistencies breaking integrations

**How She'll Use This App:**
- Deploy as internal microservice
- All legacy apps consume centralized API
- Schedule monthly sync with audit logs

---

### Persona 5: James (Mobile App Developer)

**Background:** 24-year-old mobile developer at a logistics company

**Key Characteristics:**
- Focuses on mobile-first UX and performance
- Needs offline capabilities for field workers
- Works with multiple mobile apps sharing the same backend

**Primary Goals:**
- Add location search/dropdown to driver dispatch interface
- Cache location data for offline use (poor signal areas)
- Ensure data consistency across multiple mobile apps

**Pain Points:**
- Existing PSGC solutions are web-focused, not mobile-friendly
- No clean APIs designed for mobile consumption
- Has to manually sync location data to mobile devices

**How He'll Use This App:**
- Consume JSON API endpoints: `/api/regions`, `/api/barangays/search`
- Store location data in SQLite/Realm for offline use
- Schedule background sync when WiFi available

---

### Persona 6: Patricia (Full-Stack Non-Laravel Developer)

**Background:** 30-year-old full-stack developer at a digital agency

**Key Characteristics:**
- Framework-agnostic, uses best tools for each project
- Values clean, well-documented APIs
- Manages multiple client projects simultaneously

**Primary Goals:**
- Integrate location dropdowns into client websites (e-commerce, real estate, etc.)
- Use the same PSGC data source across all projects
- Save time - no need to reinvent the wheel for each client

**Pain Points:**
- Most existing PSGC solutions are Laravel-specific (can't use!)
- Has built manual PSGC implementations multiple times (repetitive work)
- Each client wants slightly different location data formats

**How She'll Use This App:**
- Treat Laravel app as headless API service
- Consume REST endpoints from Node.js backend
- Build React components that fetch data from the API

---

## Problem Statement

**Philippine developers and organizations struggle with outdated, inconsistent, and fragmented PSGC data across their applications.** The PSA regularly updates the PSGC database, but developers either:
- Rely on outdated GitHub packages that haven't been updated in years
- Manually parse and normalize Excel files themselves (time-consuming, error-prone)
- Hardcode location arrays in their applications (impossible to update)
- Each build their own incompatible implementations (no standardization)

**Furthermore, when PSGC releases new datasets, existing solutions simply replace old data**—losing historical context and making it impossible to:
- Track changes in geographical boundaries over time
- Support applications that need historical PSGC data
- Audit and compare PSGC versions
- Revert to previous dataset versions if needed

This results in:
- **Inaccurate location data** in production applications
- **Lost historical data** when PSGC updates occur
- **Wasted development time** reinventing the wheel
- **Inconsistent data** across applications and organizations
- **No audit trail** of geographical changes

---

## Solution Approach

**PSA PSGC Data Processor** creates a reliable, normalized, and easily consumable database of Philippine geographical codes by automatically crawling, processing, and standardizing PSGC publications from the PSA website—handling special cases like Metro Manila's district structure—so that developers can access accurate, up-to-date location data through JSON APIs for use in dropdown selections and offline applications.

### Data Normalization Hierarchy

**Standard Regions (Non-NCR):**
```
Region
└── Province
    └── Municipality/City
        └── Barangay
```

**National Capital Region (NCR):**
```
Region: NCR
└── Province: Manila (treated as province level)
    └── Municipality: Tondo (labeled as "district" in PSGC, elevated to municipality level)
        └── Barangay: [actual barangays]
```

*Note: District information is preserved as an additional attribute but districts function as municipalities in the hierarchy.*

---

## Scope and Limitations

### IN SCOPE
- Automated crawling and downloading of PSGC Excel publications
- Data normalization handling special cases (Metro Manila districts → municipalities)
- Multi-version PSGC dataset support (track changes over time)
- Database storage with versioning (full data per version)
- JSON API endpoints for data retrieval (current and historical versions)
- Sync commands to update to latest PSGC dataset
- Basic administrative interface for viewing dataset versions
- Documentation for installation and usage

### OUT OF SCOPE
- User authentication/authorization (will use default Laravel setup)
- Frontend UI components (focus on API endpoints, frontend is optional/future)
- Advanced analytics/reporting on PSGC changes
- Real-time change notifications
- Map visualization (beyond dropdown selection use case)
- Multi-tenancy

### KEY CONSTRAINTS
- Must handle large datasets efficiently (~42,000 barangays)
- Must preserve old PSGC versions when syncing new ones
- Must support queries by version (e.g., "Get NCR data as of 2024-01")
- Language-agnostic API design (usable by all stacks)
- Must follow Laravel 12 conventions
- Versioning: Full data per version approach (~2-5 MB per version)

### ASSUMPTIONS
- PSGC Excel format remains consistent (or has detectable patterns)
- Updates are scheduled manually or via cron, not real-time
- Historical data doesn't need indefinite retention (configurable retention policy)
- Frontend UI is optional; primary focus is JSON API endpoints
- Using Eloquent API Resources initially, expandable to other formats later

---

## Phase 1 Breakdown: Database Foundation & Version Management

**Focus:** Establish the technical foundation for multi-version PSGC storage

**Tasks:**
1. Design database schema with versioning strategy
   - `psgc_versions` table (id, publication_date, download_url, is_current, created_at)
   - `regions` table (id, psgc_code, name, region_code, psgc_version_id)
   - `provinces` table (id, psgc_code, name, province_code, region_id, psgc_version_id)
   - `municipalities` table (id, psgc_code, name, municipality_code, province_id, is_district, psgc_version_id)
   - `barangays` table (id, psgc_code, name, barangay_code, municipality_id, psgc_version_id)

2. Create Laravel migrations

3. Create Eloquent models with relationships:
   - `Region` hasMany `Province`
   - `Province` hasMany `Municipality`
   - `Municipality` hasMany `Barangay`
   - All models belong to `PsgcVersion`

4. Add model scopes for querying by version:
   - `current()` - Query current PSGC version
   - `version($id)` - Query specific PSGC version

**Success Criteria:**
- ✅ Migrations run successfully
- ✅ Models have proper relationships (Region→Province→Municipality→Barangay)
- ✅ Can query current data: `Region::with('provinces')->current()->get()`
- ✅ Can query historical data: `Region::version(1)->get()`

**Dependencies:** None

---

## Phase 2 Breakdown: Data Processing Pipeline

**Focus:** Build the complete data pipeline from Excel to database

**Tasks:**
1. **PSA Crawler**
   - Create Action: `DownloadPsgc`
   - Scrape PSA website to find latest PSGC publication URL
   - Download Excel file to storage
   - Handle errors (network issues, file not found)

2. **Excel Parser**
   - Create Action: `ParsePsgcExcel`
   - Parse Excel file (multiple sheets/regions)
   - Extract: Region, Province, Municipality, Barangay data
   - Handle Excel variations (different column layouts)

3. **Data Normalizer**
   - Create Action: `NormalizePsgcRow`
   - Implement special case: Manila districts → municipalities
   - Store district info in `is_district` flag
   - Normalize codes to consistent format

4. **Data Import Process**
   - Create Action: `ImportPsgcData`
   - Create new `PsgcVersion` record
   - Import normalized data with version linkage
   - Validate data integrity (unique codes, proper relationships)
   - Mark new version as `is_current`

**Success Criteria:**
- ✅ Can download latest PSGC Excel from PSA website
- ✅ Parser extracts all 4 levels (Region→Province→Municipality→Barangay)
- ✅ Manila districts are normalized to municipalities with `is_district=true`
- ✅ Data imports to database with proper version linkage
- ✅ Old versions remain intact (no data loss)

**Dependencies:** Phase 1

---

## Phase 3 Breakdown: API Layer & Sync Command

**Focus:** Expose PSGC data via clean JSON APIs and provide sync automation

**Tasks:**
1. **Eloquent API Resources**
   - Create `RegionResource`, `ProvinceResource`, `MunicipalityResource`, `BarangayResource`
   - Transform models with proper type hints
   - Include relationships where appropriate

2. **JSON API Endpoints**
   - `GET /api/regions` - List all current regions
   - `GET /api/regions/{id}` - Show region with provinces
   - `GET /api/provinces` - List provinces (filter by region)
   - `GET /api/municipalities` - List municipalities (filter by province)
   - `GET /api/barangays` - List barangays (filter by municipality)

3. **Artisan Sync Command**
   - Create Command: `php artisan psgc:sync`
   - Orchestrates: Download → Parse → Normalize → Import
   - Show progress and statistics (e.g., "Imported 17 regions, 82 provinces...")
   - Rollback on error

4. **Basic Filtering Support**
   - Query parameter filters: `?region_id=12&province_id=3`
   - Pagination support

**Success Criteria:**
- ✅ API endpoints return valid JSON with PSGC data
- ✅ Can chain relationships: `/api/regions/1/provinces/5/municipalities`
- ✅ Sync command runs without errors
- ✅ After sync, new data is available via API

**Dependencies:** Phase 2

---

## Phase 4 Breakdown: Enhanced Features

**Focus:** Add historical queries and advanced data capabilities

**Tasks:**
1. **Historical API Endpoints**
   - Query by version: `GET /api/regions?version=2`
   - Get specific version: `GET /api/psgc-versions/{id}`

2. **Change/Diff Tracking**
   - Compare two versions: `GET /api/psgc-versions/diff/1/2`
   - Show: Added barangays, renamed municipalities, etc.

3. **Search API**
   - Global search: `GET /api/search?q=Tondo`
   - Returns: Matches with full hierarchy (Tondo → Manila → NCR)

4. **Advanced Filtering**
   - Filter by multiple criteria
   - Include relationships

**Success Criteria:**
- ✅ Can query historical PSGC data
- ✅ Diff shows accurate changes between versions
- ✅ Search returns relevant results with hierarchy
- ✅ Filtering works as expected

**Dependencies:** Phase 3

---

## Phase 5 Breakdown: Polish & Admin

**Focus:** Add performance optimizations and management tools

**Tasks:**
1. **API Caching Layer**
   - Cache API responses (Redis)
   - Cache invalidation on sync

2. **Basic Admin Dashboard**
   - List PSGC versions with dates
   - View version details (counts: regions, provinces, etc.)
   - Download original Excel file

3. **Data Validation**
   - Validate PSGC codes format
   - Check for duplicate names within version
   - Validate relationships exist

4. **Rollback Command**
   - `php artisan psgc:rollback {version_id}` - Set previous version as current
   - Confirm action with warning

**Success Criteria:**
- ✅ Cached responses are faster
- ✅ Dashboard shows all versions and metadata
- ✅ Validation catches data errors before import
- ✅ Rollback successfully switches current version

**Dependencies:** Phase 4

---

## Roadmap Rationale

**Why Phase 1 first?** Database foundation must exist before any data processing. Version management is critical to the project's core value proposition.

**Why Phase 2 next?** Data pipeline is the core value. Without it, APIs have no data.

**Why Phase 3 then?** Once data flows, expose it via APIs. This delivers the primary user benefit (JSON endpoints).

**Why Phase 4?** After basic functionality, add enhanced features that differentiate this from legacy solutions.

**Why Phase 5 last?** Polish and admin tools are nice-to-have but not critical for initial release.
