# Feature Specification: PSA Crawler

**Date**: 2026-01-13
**Status**: Approved
**Related Phase**: Phase 2 - Data Processing Pipeline
**Depends On**: None

---

## Overview

### Purpose

The PSA Crawler provides a reliable mechanism to download PSGC (Philippine Standard Geographic Code) publication Excel files from the Philippine Statistics Authority website. This is the first critical step in the data processing pipeline, enabling subsequent parsing, normalization, and database import of Philippine geographical data.

This feature implements a manual download workflow where the system guides users through downloading the file from the protected PSA website, then processes it for use in the application.

### User Value

**For Developers (Alex, Sarah, Marco, Elena, James, Patricia):**
- Eliminates manual URL hunting and file management
- Provides automated file validation and versioning
- Establishes a standardized, repeatable process for obtaining PSGC data
- Reduces errors from manual file handling

**For Project Maintainability:**
- Centralizes PSGC data acquisition logic
- Supports easy upgrading to automated crawling later
- Ensures consistent file handling across the data pipeline

### Alignment with Mission

This feature directly supports the mission by automating the first step of the data pipeline: "automatically crawling, processing, and standardizing PSGC publications." While initially manual due to Cloudflare protection, it establishes the foundation for full automation while providing immediate value through guided workflow.

---

## User Stories

### Primary User Stories

- As a **developer (Sarah)**, I want to be guided through downloading the latest PSGC Excel file so that I can be confident I'm using the correct data source without manually navigating the PSA website.

- As a **developer (Marco)**, I want the system to validate the downloaded Excel file structure so that I'm alerted if the file is corrupted or in an unexpected format.

- As a **project maintainer (Elena)**, I want downloaded files stored with proper version tracking so that I can reference which dataset version each import corresponds to.

- As a **backend engineer (Marco)**, I want the Excel file saved to Laravel's storage system so that subsequent processing stages can reliably access it.

### Edge Cases & Alternative Flows

- As a **developer (Alex)**, when the PSA website structure changes, I want clear error messages so that I can troubleshoot the issue or update the crawler logic.

- As a **developer (Patricia)**, when downloading a PSGC file that already exists locally, I want to be prompted whether to overwrite or keep the existing file.

- As a **developer (James)**, when the downloaded file is corrupted or unreadable, I want validation to fail early so that I don't waste time trying to parse bad data.

- As a **project maintainer (Elena)**, when manually downloading a file from PSA due to Cloudflare protection, I want the system to recognize the file version from the filename and store it appropriately.

---

## Detailed Requirements

### Functional Requirements

1. **FR-001** The system shall provide an Artisan command `php artisan psgc:sync` that initiates the PSGC data synchronization process.

2. **FR-002** The system shall check for existing PSGC Excel files in `storage/app/psgc/` and prompt the user whether to overwrite or keep existing files.

3. **FR-003** The system shall validate that downloaded Excel files contain the required sheets: `PSGC`, `Metadata`, `National Summary`, `Prov Sum`, `Notes`, and `Coding Structure`.

4. **FR-004** The system shall extract the version information from the PSGC Excel filename following the pattern: `PSGC-{Quarter}-{Year}-Publication-Datafile.xlsx`.

5. **FR-005** The system shall store downloaded PSGC Excel files in `storage/app/psgc/` directory using the original filename.

6. **FR-006** The system shall validate the Excel file structure including:
   - Presence of `PSGC` sheet
   - Required columns in `PSGC` sheet: `10-digit PSGC`, `Name`, `Correspondence Code`, `Geographic Level`

7. **FR-007** The system shall provide clear console output messages indicating:
   - Current PSGC file status (exists or not)
   - Where to download the file from (PSA URL)
   - Validation results (pass/fail)
   - Where the file was saved

8. **FR-008** The system shall provide an option to view the PSGC publication URL in the browser using Laravel's `open` command or manual instruction.

9. **FR-009** The system shall accept a local file path as an argument to bypass the download step (for advanced users who already have the file).

10. **FR-010** The system shall return appropriate exit codes:
    - Exit code 0: Success
    - Exit code 1: Validation failed
    - Exit code 2: File not found
    - Exit code 3: User cancelled operation

### User Interface Requirements

1. **UI-001** The command interface shall display the PSA URL: `https://psa.gov.ph/classification/psgc`

2. **UI-002** The system shall prompt users for confirmation before overwriting existing files.

3. **UI-003** The system shall display validation results in a clear, scannable format:
   ```
   ✓ PSGC sheet found
   ✓ Required columns present
   ✓ File validated successfully
   ```

4. **UI-004** The system shall provide a "Manual Download" instruction block when Cloudflare or network issues prevent direct download.

### Data Requirements

1. **DR-001** The system shall store PSGC Excel files with their original filenames (e.g., `PSGC-4Q-2025-Publication-Datafile.xlsx`).

2. **DR-002** The system shall validate the PSGC sheet contains the following columns:
   - `10-digit PSGC` - Primary identifier (10-digit string)
   - `Name` - Official geographic name
   - `Correspondence Code` - Reference to previous PSGC codes
   - `Geographic Level` - Level indicator (Region, Province, City, Municipality, Barangay, SubMun)
   - `Old Names` - Previous official names (optional)
   - `Status` - Administrative or legal status (optional)
   - `City Class` - City classification (optional)
   - `Income Classification (DOF DO No. 074.2024)` - Income class (optional)
   - `2024 Population` - Population count (optional)
   - `Urban / Rural (based on 2020 CPH)` - U or R classification (optional)

3. **DR-003** The system shall parse version information from filename using regex pattern: `PSGC-(\dQ)-(\d{4})-Publication-Datafile\.xlsx`

4. **DR-004** The system shall treat `Geographic Level` values case-insensitively (support "Region", "region", "REGION").

5. **DR-005** The system shall handle `SubMun` values in `Geographic Level` column as equivalent to "District" for Manila special case normalization.

**Data Model (PSGC Sheet Structure):**

| Column Name | Data Type | Required | Description |
| ------------ | ----------- | --------- | ------------- |
| `10-digit PSGC` | String(10) | Yes | Primary identifier, 10-digit fixed-length |
| `Name` | String | Yes | Official geographic name |
| `Correspondence Code` | String | Yes | Reference to previous PSGC codes |
| `Geographic Level` | String | Yes | Level indicator: Region, Province, City, Municipality, Barangay, SubMun |
| `Old Names` | String | No | Previous official names (if applicable) |
| `Status` | String | No | Administrative or legal status |
| `City Class` | String | No | City classification (e.g., HUC) |
| `Income Classification (DOF DO No. 074.2024)` | String | No | Income class of LGUs |
| `2024 Population` | Numeric | No | Official population count |
| `Urban / Rural (based on 2020 CPH)` | String(1) | No | U or R classification |

**PSGC Code Structure:**
```
Positions 1-2: Region code
Positions 3-4: Province code
Positions 5-6: City/Municipality code
Positions 7-10: Barangay code
```

### Business Logic & Rules

1. **BL-001** When a PSGC file already exists in `storage/app/psgc/`, the system shall prompt the user with three options:
   - (O) Overwrite existing file
   - (K) Keep existing file and skip download
   - (C) Cancel operation

2. **BL-002** When validating the Excel file, the system shall consider the `PSGC` sheet as the only required sheet for data import purposes.

3. **BL-003** When detecting `Geographic Level = SubMun`, the system shall treat this as a district for subsequent normalization logic (specifically for Manila/NCR case).

4. **BL-004** When providing manual download instructions, the system shall display:
   - Direct URL to PSGC publications page
   - Steps to navigate to the "Download PSGC Publications" section
   - Instruction to save file to `storage/app/psgc/`

5. **BL-005** When accepting a local file path via argument, the system shall validate the file exists before proceeding.

6. **BL-006** When validation fails, the system shall provide specific error messages:
   - "PSGC sheet not found in Excel file"
   - "Required column '{column}' is missing"
   - "File is corrupted or invalid Excel format"

### Security & Permissions

1. **SEC-001** The command shall be accessible only to users with console/artisan access (no additional authentication required for local CLI use).

2. **SEC-002** Downloaded files shall be stored in `storage/app/psgc/` (outside public directory) to prevent unauthorized web access.

3. **SEC-003** File paths provided as arguments shall be validated to prevent directory traversal attacks (must be within allowed directories).

### Performance Requirements

1. **PERF-001** File validation shall complete within 5 seconds for typical PSGC Excel files (~2-5 MB).

2. **PERF-002** The command shall provide progress indicators for operations taking longer than 2 seconds.

---

## Technical Approach

**Note**: Referenced `.references/design-pattern.md` for alignment with established patterns.

### Architecture Overview

The PSA Crawler implements a **manual-first workflow** with clear upgrade path to automated crawling:

1. **Command Layer**: Artisan console command handles user interaction
2. **Validation Layer**: Service class validates Excel file structure
3. **Storage Layer**: Laravel Storage facade handles file operations
4. **Configuration Layer**: Configuration for URLs, paths, and validation rules

**Design Pattern**: Following the project's Action pattern, the crawler uses a dedicated `SyncPsgc` Action to orchestrate the sync process. File validation is handled by a separate `ValidatePsgcExcel` Action for testability.

### Technology Stack

- **Backend**: Laravel 12 (PHP 8.3)
- **Console**: Symfony Console (via Laravel Artisan)
- **Excel Processing**: Laravel Excel (PhpSpreadsheet wrapper)
- **File Storage**: Laravel Storage (local filesystem)
- **Testing**: Pest 4

**Required Packages**:
- `maatwebsite/excel` or `phpoffice/phpspreadsheet` - For Excel validation
- (No additional packages for manual download - using Laravel's built-in Storage)

### Integration Points

1. **Storage System**: Use `Storage::disk('local')->path('psgc/')` for file operations
2. **Excel Parser**: Integration point with Phase 2's Excel Parser Action
3. **Command Interface**: Integration with Laravel's console signature system
4. **Configuration**: Use `config/psgc.php` for URLs and validation rules

### Dependencies

- **Feature Dependencies**: None (foundational feature)
- **Technical Dependencies**:
  - Laravel Storage system
  - Console/Artisan system
  - PhpSpreadsheet for Excel validation
- **Data Dependencies**:
  - PSGC Excel file (user-provided)
  - No database migrations required for this phase

---

## Implementation Tasks

### Task Breakdown

This feature can be implemented through the following interconnected tasks:

#### Setup & Foundation

- [ ] **Task 1: Create Configuration File**
  - Details: Create `config/psgc.php` with PSA URL, storage path, and validation rules
  - Estimated Complexity: Small
  - Depends On: None

- [ ] **Task 2: Create Storage Directory**
  - Details: Ensure `storage/app/psgc/` directory exists with proper permissions
  - Estimated Complexity: Small
  - Depends On: Task 1

- [ ] **Task 3: Create Artisan Command**
  - Details: Run `php artisan make:command Psgc/SyncCommand` to generate command file
  - Estimated Complexity: Small
  - Depends On: None

#### Core Functionality

- [ ] **Task 4: Implement Command Signature and Arguments**
  - Details: Define command signature with optional `--path` argument for local file path
  - Estimated Complexity: Small
  - Depends On: Task 3

- [ ] **Task 5: Implement File Existence Check**
  - Details: Check for existing PSGC files and prompt for overwrite/keep/cancel
  - Estimated Complexity: Medium
  - Depends On: Task 2, Task 4

- [ ] **Task 6: Implement URL Display and Instructions**
  - Details: Display PSA URL and manual download instructions
  - Estimated Complexity: Small
  - Depends On: Task 1, Task 4

- [ ] **Task 7: Create Excel Validation Action**
  - Details: Create `app/Actions/Psgc/ValidatePsgcExcel.php` to validate file structure
  - Estimated Complexity: Medium
  - Depends On: None

- [ ] **Task 8: Implement Validation Logic**
  - Details: Check for PSGC sheet and required columns using PhpSpreadsheet
  - Estimated Complexity: Medium
  - Depends On: Task 7

- [ ] **Task 9: Implement File Copy/Move**
  - Details: Copy user-provided file to `storage/app/psgc/` with proper naming
  - Estimated Complexity: Small
  - Depends On: Task 5, Task 8

- [ ] **Task 10: Implement Version Extraction**
  - Details: Parse version (Quarter, Year) from filename using regex
  - Estimated Complexity: Medium
  - Depends On: Task 9

- [ ] **Task 11: Implement Success/Error Output**
  - Details: Display formatted console output for all success and error scenarios
  - Estimated Complexity: Medium
  - Depends On: Task 8, Task 10

#### Testing

- [ ] **Task 12: Create Feature Test File**
  - Details: Run `php artisan make:test --pest Feature/Psgc/SyncCommandTest`
  - Estimated Complexity: Small
  - Depends On: Task 3

- [ ] **Task 13: Test File Validation**
  - Details: Test with valid and invalid Excel files (missing sheets, missing columns)
  - Estimated Complexity: Medium
  - Depends On: Task 8, Task 12

- [ ] **Task 14: Test File Overwrite Prompt**
  - Details: Test user prompts when file exists (overwrite, keep, cancel)
  - Estimated Complexity: Medium
  - Depends On: Task 5, Task 12

- [ ] **Task 15: Test Local File Path Argument**
  - Details: Test `--path` argument with valid and invalid file paths
  - Estimated Complexity: Medium
  - Depends On: Task 9, Task 12

- [ ] **Task 16: Test Error Handling**
  - Details: Test network errors, corrupted files, permission issues
  - Estimated Complexity: Medium
  - Depends On: Task 8, Task 12

#### Documentation & Polish

- [ ] **Task 17: Add Command to Artisan List**
  - Details: Ensure command appears in `php artisan list` output
  - Estimated Complexity: Small
  - Depends On: Task 3

- [ ] **Task 18: Create Documentation**
  - Details: Document command usage, arguments, and examples in `docs/psgc-sync.md`
  - Estimated Complexity: Small
  - Depends On: Task 11

**Task Dependency Graph:**

```
Task 1 (Config) ────────────────┬─> Task 6 (URL Display)
                               │
Task 2 (Storage) ──────┬──────┼─> Task 5 (Existence Check)
                      │         │
                      └─> Task 9 (File Copy) ──> Task 10 (Version Extraction) ──┐
                                                                    │
Task 3 (Command) ────────────────┬───> Task 4 (Signature) ───────────────────────────────┼─> Task 11 (Output)
                                │                                        │
                                └─> Task 7 (Validation Action) ──> Task 8 (Validation Logic) ──┘

Task 12 (Test File) ────────────────────────────────┬─> Task 13 (Validation Tests)
                                                 │
                                                 ├─> Task 14 (Overwrite Tests)
                                                 │
                                                 ├─> Task 15 (Path Argument Tests)
                                                 │
                                                 └─> Task 16 (Error Handling Tests)

Task 17 (Artisan List) ──────────────────────────────────────────────────────────────> Task 18 (Documentation)
```

---

## Acceptance Criteria

### Feature Acceptance

The feature is considered complete when:

1. Running `php artisan psgc:sync` displays the PSA publication URL and manual download instructions.

2. User is prompted to provide a local file path or download from PSA website.

3. When a file is provided, the system validates it contains the PSGC sheet with all required columns.

4. Valid files are copied to `storage/app/psgc/` with version information extracted from the filename.

5. When a file already exists, user is prompted to overwrite, keep, or cancel.

6. Invalid files (missing PSGC sheet, missing columns) fail validation with specific error messages.

7. Command returns appropriate exit codes (0 for success, non-zero for failures).

8. All test scenarios pass with Pest.

### Test Scenarios

1. **Scenario**: User runs command without existing file
   - **Given**: No PSGC files exist in `storage/app/psgc/`
   - **When**: User runs `php artisan psgc:sync`
   - **Then**: Command displays PSA URL, prompts for file path, validates file, saves successfully with exit code 0

2. **Scenario**: User provides local file path with valid Excel
   - **Given**: User runs `php artisan psgc:sync --path=/tmp/PSGC-4Q-2025-Publication-Datafile.xlsx`
   - **When**: File exists and is valid
   - **Then**: File is validated, copied to storage, version extracted, success message displayed, exit code 0

3. **Scenario**: User provides invalid Excel file (missing PSGC sheet)
   - **Given**: User provides Excel file without PSGC sheet
   - **When**: Validation runs
   - **Then**: Error message "PSGC sheet not found in Excel file" displayed, exit code 1

4. **Scenario**: User provides invalid Excel file (missing required column)
   - **Given**: User provides Excel file missing `10-digit PSGC` column
   - **When**: Validation runs
   - **Then**: Error message "Required column '10-digit PSGC' is missing" displayed, exit code 1

5. **Scenario**: File already exists, user chooses to overwrite
   - **Given**: Existing file in `storage/app/psgc/PSGC-4Q-2025-Publication-Datafile.xlsx`
   - **When**: User runs `php artisan psgc:sync` and chooses (O)verwrite
   - **Then**: User is prompted for new file, old file is replaced, success message displayed

6. **Scenario**: File already exists, user chooses to keep
   - **Given**: Existing file in storage
   - **When**: User runs `php artisan psgc:sync` and chooses (K)eep
   - **Then**: Command displays "Using existing PSGC file" and exits with success

7. **Scenario**: File already exists, user chooses to cancel
   - **Given**: Existing file in storage
   - **When**: User runs `php artisan psgc:sync` and chooses (C)ancel
   - **Then**: Command displays "Operation cancelled" and exits with code 3

8. **Scenario**: Version extraction from filename
   - **Given**: File named `PSGC-4Q-2025-Publication-Datafile.xlsx`
   - **When**: Version is extracted
   - **Then**: Quarter = "4Q", Year = "2025"

9. **Scenario**: Geographic Level contains SubMun value
   - **Given**: Excel file has row with `Geographic Level = SubMun`
   - **When**: Validation runs
   - **Then**: Validation passes (SubMun is recognized as valid level for Manila districts)

10. **Scenario**: Corrupted or invalid Excel file
    - **Given**: User provides corrupted file (not valid Excel format)
    - **When**: Validation runs
    - **Then**: Error message "File is corrupted or invalid Excel format" displayed, exit code 2

---

## Open Questions & Risks

### Open Questions

1. **Question**: Should the system support downloading from a URL if user provides one, or strictly manual download?
   - **Current Approach**: Manual download only with local file path argument
   - **Rationale**: Cloudflare protection complicates automated downloads; manual is reliable first step
   - **Future**: Can add automated download as enhancement once approach is validated

2. **Question**: Should the command attempt to auto-detect PSGC files in a default location (like Downloads folder) to simplify user experience?
   - **Current Approach**: Requires explicit file path or manual instruction
   - **Rationale**: Explicit path is more predictable and error-free
   - **Future**: Could add intelligent file detection as enhancement

3. **Question**: Should the system validate sheet names beyond the PSGC sheet?
   - **Current Approach**: Only validate PSGC sheet presence and columns
   - **Rationale**: PSGC sheet is the only required sheet for data import
   - **Future**: Could validate all sheets for completeness

### Known Risks

1. **Risk**: PSA website structure changes, breaking URL or navigation instructions
   - **Impact**: Medium
   - **Mitigation**: Store URL in configuration file for easy updates; provide clear error messages; document manual download steps

2. **Risk**: Excel file format changes in future PSA publications
   - **Impact**: High (would break validation)
   - **Mitigation**: Validate only required columns and PSGC sheet; make validation rules configurable; test with multiple PSA publications to identify variations

3. **Risk**: Users provide large or multiple PSGC files, causing confusion
   - **Impact**: Low
   - **Mitigation**: Clear prompts for single file selection; validation catches invalid files; exit codes for proper error handling

4. **Risk**: Permission issues when writing to `storage/app/psgc/`
   - **Impact**: Medium
   - **Impact**: Could prevent file storage
   - **Mitigation**: Check directory permissions in command; provide clear error message with fix instructions; use Laravel Storage which handles permissions

5. **Risk**: PSGC Excel file exceeds PHP memory limits during validation
   - **Impact**: Low
   - **Rationale**: Typical PSGC files are ~2-5 MB, well within default limits
   - **Mitigation**: Use PhpSpreadsheet's read filter to load only required sheets/rows; monitor file size in validation

---

## Success Metrics

### Key Performance Indicators

- **Command Success Rate**: 95%+ of attempts should succeed with valid files
- **Validation Accuracy**: 100% of invalid files should be caught before parsing
- **User Error Rate**: <5% of users should encounter unexpected errors
- **File Validation Time**: <5 seconds for typical PSGC files

### User Feedback

- **Documentation Clarity**: Users can run command and complete sync without additional help
- **Error Messages**: Error messages are actionable and point to specific issues
- **Upgrade Path**: Future transition to automated download feels natural and requires minimal changes

---

## References

- **Mission**: `.references/mission.md`
- **Roadmap**: `.references/roadmap.md`
- **Design Patterns**: `.references/design-pattern.md`
- **Data Structure**: `structure.md` (provided by user)
- **Sample Excel File**: `PSGC-4Q-2025-Publication-Datafile.xlsx`
- **Related Specs**: (None yet - this is first specification)
- **External Resources**:
  - PSA PSGC Publications: https://psa.gov.ph/classification/psgc
  - PhpSpreadsheet Documentation: https://phpspreadsheet.readthedocs.io/
  - Laravel Console Commands: https://laravel.com/docs/console
  - Laravel Storage: https://laravel.com/docs/filesystem

---

## Revision History

| Date | Author | Changes |
|------|--------|---------|
| 2026-01-13 | Apollo Agent | Initial specification created for manual download PSA Crawler |

---

## Implementation Notes

### Excel File Structure Reference

The PSGC Excel workbook contains multiple sheets:

**Core Sheet (Primary Data Source):**
- **PSGC** - Contains all geographic units with hierarchy encoded via PSGC code prefixes
  - One row per geographic unit
  - Geographic levels: Region, Province, City, Municipality, Barangay, SubMun

**Supporting Sheets (Reference Only):**
- **Metadata** - Dataset documentation and provenance
- **National Summary** - National-level aggregation
- **Prov Sum** - Provincial summary statistics
- **Notes** - Legal and technical annotations
- **Coding Structure** - Formal definition of PSGC coding system

### PSGC Code Hierarchy

Parent-child relationships are derived using **prefix matching**:

```
Region:      0900000000 (first 2 digits = region code)
Province:     0901000000 (first 4 digits = province code)
Municipality: 0901050000 (first 6 digits = city/municipality code)
Barangay:     0901050011 (all 10 digits = full code)
```

### Manila District Special Case

- **SubMun** in `Geographic Level` column indicates a district (specifically for Manila/NCR)
- These will be normalized to **Municipality** level in Phase 2's Data Normalizer
- The `SubMun` value is preserved in validation but marked for special handling

### Future Enhancement Path

When upgrading to **automated download**:
1. Add HTTP client with Cloudflare bypass (browser automation or headless Chrome)
2. Implement web scraping logic to extract latest publication link
3. Add retry logic for network failures
4. Add automatic file download and storage
5. Maintain backward compatibility with `--path` argument for manual override
