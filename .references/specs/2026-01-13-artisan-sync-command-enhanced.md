# Feature Specification: Artisan Sync Command (Enhanced)

**Date**: 2026-01-13
**Status**: Draft
**Related Phase**: Phase 2
**Depends On**: PSA Crawler (#1), Excel Parser (#2), Data Import Process (#6)

---

## Overview

### Purpose
Enhance the existing `php artisan psgc:sync` Artisan command to fully automate the PSGC data synchronization process—from automatically downloading the latest Excel publication from the PSA website through validation, parsing, and database import—with progress feedback and error handling.

### User Value
- **Sarah (Full-Stack Laravel Developer)**: Run `ddev php artisan psgc:sync` without manually downloading Excel files or navigating PSA website
- **Marco (Backend API Developer)**: Schedule automated syncs via cron jobs with confidence that the entire pipeline works end-to-end
- **Elena (Enterprise Developer)**: Deploy microservice with one-command updates to latest PSGC data, reducing manual intervention and errors

### Alignment with Mission
Delivers on the mission's "Automate Data Acquisition" requirement by creating a complete, automated pipeline that developers can run with a single command—eliminating manual file downloads and enabling reliable, scheduled updates.

---

## User Stories

### Primary User Stories
- As a **Laravel Developer**, I want to run `php artisan psgc:sync` so that the latest PSGC data is automatically downloaded and imported without manual steps.
- As a **DevOps Engineer**, I want to schedule `php artisan psgc:sync` via cron so that the PSGC database stays current without human intervention.
- As a **Backend Developer**, I want to see clear progress messages and statistics during sync so that I can monitor the operation and confirm success.
- As a **System Administrator**, I want the command to handle errors gracefully and rollback on failure so that the database is never left in a corrupted state.

### Edge Cases & Alternative Flows
- As a **Developer**, when the PSA website is down or unreachable, I want the command to fail with a clear error message and not modify the database.
- As a **Developer**, when the downloaded Excel file is corrupted or invalid, I want the command to validate before import and abort without changing existing data.
- As a **Developer**, when I already have a PSGC Excel file locally, I want to specify it with the `--path` argument to skip automatic download.
- As a **Developer**, when I want to force a re-import of the same file, I want to use the `--force` flag to override existing data validation checks.

---

## Detailed Requirements

### Functional Requirements
1. [FR-001] The system shall automatically detect and download the latest PSGC publication Excel file from the PSA website when no `--path` argument is provided.
2. [FR-002] The system shall accept a `--path` argument to use a local Excel file instead of automatic download.
3. [FR-003] The system shall validate the downloaded or provided Excel file against PSGC requirements before import.
4. [FR-004] The system shall parse and import PSGC data into the database using the existing `ImportPsgcData` action.
5. [FR-005] The system shall display progress messages for each phase: Download, Validate, Import.
6. [FR-006] The system shall display a statistics summary after successful import: regions, provinces, municipalities, barangays counts.
7. [FR-007] The system shall support a `--force` flag to bypass validation checks and re-import the same file.
8. [FR-008] The system shall use database transactions for the entire sync operation to ensure atomicity.
9. [FR-009] The system shall rollback all changes if any phase fails (download, validation, or import).
10. [FR-010] The system shall return appropriate exit codes: 0 for success, 1 for validation failure, 2 for download failure, 3 for import failure.

### User Interface Requirements
1. [UI-001] The command shall display formatted progress output with color-coded messages (info, success, warning, error).
2. [UI-002] The command shall show a loading spinner or progress indicator during long-running operations.
3. [UI-003] The command shall display a final summary table with counts per geographic level.

### Data Requirements
1. [DR-001] The system shall store the downloaded Excel file in the `storage/app/psgc/` directory.
2. [DR-002] The system shall preserve the original filename with version information (e.g., `PSGC-4Q-2025-Publication-Datafile.xlsx`).
3. [DR-003] The system shall maintain a sync log or history table (optional) to track previous syncs.

**Data Model**:
- Uses existing `Region`, `Province`, `CityMunicipality`, `Barangay` models
- Leverages `ValidatePsgcExcel` action for file validation
- Leverages `ImportPsgcData` action for data processing
- Optional: `PsgcSync` table (id, file_path, synced_at, regions_count, provinces_count, success)

### Business Logic & Rules
1. [BL-001] When the PSA website returns multiple publication links, the system shall select the most recent by publication date.
2. [BL-002] When a file is manually specified via `--path`, the system shall skip automatic download.
3. [BL-003] When validation fails, the system shall display all error messages and exit with code 1.
4. [BL-004] When import fails mid-operation, the system shall rollback the entire database transaction.
5. [BL-005] When using `--force`, the system shall skip validation and proceed directly to import (advanced use case).

### Security & Permissions
1. [SEC-001] Only users with file system write permissions can execute the command (OS-level enforcement).
2. [SEC-002] The command shall sanitize file paths to prevent directory traversal attacks.
3. [SEC-003] Downloaded files shall be validated as Excel files (by extension and content) before processing.

### Performance Requirements
1. [PERF-001] The download phase shall complete within 30 seconds for typical PSGC file sizes (~3-5 MB).
2. [PERF-002] The import phase shall complete within 60 seconds for a full PSGC dataset (~42,000 barangays).
3. [PERF-003] The command shall support progress indicators without blocking output buffers.

---

## Technical Approach

**Note**: Reference `.references/design-pattern.md` to ensure alignment with established patterns and conventions.

### Architecture Overview
The enhanced sync command follows the **Action pattern** established in the codebase. It orchestrates multiple discrete actions in a coordinated sequence:
1. **CrawlPsgcWebsite** action (NEW): Scrape PSA website for the latest download URL
2. **DownloadPsgc** action (NEW): Download Excel file to storage
3. **ValidatePsgcExcel** action (EXISTS): Validate file structure and content
4. **ImportPsgcData** action (EXISTS): Parse and import to the database

The command serves as an **orchestrator** using Laravel's database transactions for atomicity.

### Technology Stack
- **Backend**: Laravel 12 Artisan Commands, Actions
- **HTTP Client**: Laravel `Http` facade or `Guzzle`
- **Excel Processing**: `phpoffice/phpspreadsheet` (already installed)
- **Storage**: Laravel Storage facade (local disk)
- **Transactions**: Laravel `DB` facade

### Integration Points
1. **Integration with PSA Website**: Scrape `https://psa.gov.ph/classification/psgc` for download links
2. **Integration with ValidatePsgcExcel Action**: Validates file before import
3. **Integration with ImportPsgcData Action**: Handles parsing and database operations
4. **Integration with Storage**: Downloads to `storage/app/psgc/` directory

### Dependencies
- **Feature Dependencies**: PSA Crawler (#1) - Must create this action first, Excel Parser (#2) - exists, Data Import Process (#6) - exists
- **Technical Dependencies**: `phpoffice/phpspreadsheet`, `guzzlehttp/guzzle` (or Laravel Http)
- **Data Dependencies**: Existing `Region`, `Province`, `CityMunicipality`, `Barangay` models

---

## Implementation Tasks

### Task Breakdown

This feature can be implemented through the following interconnected tasks:

#### Setup & Foundation
- [ ] **Task 1**: Create `CrawlPsgcWebsite` Action
  - **Details**: Scrape PSA website to find the latest PSGC publication download URL
  - **Subtasks**:
    - Fetch PSA classification page HTML
    - Parse HTML for download links (pattern: PSGC-{Quarter}-{Year}-Publication-Datafile.xlsx)
    - Extract the most recent publication by date
    - Return absolute URL or null if not found
  - **Estimated Complexity**: Medium
  - **Depends On**: None

- [ ] **Task 2**: Create `DownloadPsgc` Action
  - **Details**: Download PSGC Excel file from given URL to storage
  - **Subtasks**:
    - Validate URL is HTTPS and from psa.gov.ph domain
    - Download file to a temporary location
    - Verify the file is a valid Excel (extension, basic structure)
    - Move to final storage location: `storage/app/psgc/{filename}`
    - Return absolute file path
  - **Estimated Complexity**: Small
  - **Depends On**: Task 1

#### Core Functionality
- [ ] **Task 3**: Enhance `SyncCommand` to orchestrate actions
  - **Details**: Integrate crawl, download, validate, and import actions in sequence
  - **Subtasks**:
    - Add `--force` flag to command signature
    - Implement `handle()` method with database transaction
    - Call `CrawlPsgcWebsite` if no `--path` is provided
    - Call `DownloadPsgc` with the crawled URL
    - Call `ValidatePsgcExcel` (or skip if `--force`)
    - Call `ImportPsgcData`
    - Display progress messages with colors
    - Display summary statistics
    - Handle exceptions with rollback
    - Return appropriate exit codes
  - **Estimated Complexity**: Medium
  - **Depends On**: Task 1, Task 2

- [ ] **Task 4**: Add progress output formatting
  - **Details**: Implement user-friendly progress messages
  - **Subtasks**:
    - Create output formatting methods (info, success, warning, error)
    - Add step separators and newlines
    - Format summary as a table or list
  - **Estimated Complexity**: Small
  - **Depends On**: Task 3

#### Integration & Testing
- [ ] **Task 5**: Create `CrawlPsgcWebsite` tests
  - **Details**: Unit tests for website crawling logic
  - **Subtasks**:
    - Test HTML parsing with mock responses
    - Test date extraction and sorting
    - Test error handling (no links found)
  - **Estimated Complexity**: Medium
  - **Depends On**: Task 1

- [ ] **Task 6**: Create `DownloadPsgc` tests
  - **Details**: Unit tests for the download action
  - **Subtasks**:
    - Test URL validation (rejects non-PSA domains)
    - Test file download with mock HTTP responses
    - Test file integrity checks
  - **Estimated Complexity**: Medium
  - **Depends On**: Task 2

- [ ] **Task 7**: Create enhanced `SyncCommand` tests
  - **Details**: Feature tests for complete sync orchestration
  - **Subtasks**:
    - Test successful sync (crawl → download → validate → import)
    - Test sync with `--path` argument
    - Test sync with `--force` flag
    - Test validation failure (no database changes)
    - Test download failure (no database changes)
    - Test transaction rollback on import error
    - Test exit codes
  - **Estimated Complexity**: Large
  - **Depends On**: Task 3

#### Documentation & Polish
- [ ] **Task 8**: Update command help text
  - **Details**: Ensure `php artisan psgc:sync --help` is clear
  - **Subtasks**:
    - Document `--path` argument
    - Document `--force` flag
    - Add usage examples
  - **Estimated Complexity**: Small
  - **Depends On**: Task 3

**Task Dependency Graph**:
```
Task 1 (CrawlPsgcWebsite)
    ↓
Task 2 (DownloadPsgc)
    ↓
Task 3 (Enhance SyncCommand) ←→ Task 4 (Progress Formatting) [parallel]
    ↓
Task 5, 6, 7 (Tests) [parallel]
    ↓
Task 8 (Documentation)
```

---

## Acceptance Criteria

### Feature Acceptance
The feature is considered complete when:
1. Running `ddev php artisan psgc:sync` without arguments automatically downloads the latest PSGC file and imports it.
2. Running `ddev php artisan psgc:sync --path=/local/file.xlsx` uses the specified file without downloading.
3. Running `ddev php artisan psgc:sync --force` skips validation and imports the file.
4. The command displays colored progress messages for each phase.
5. The command displays a summary with counts: regions, provinces, cities/municipalities, barangays.
6. On any failure, no changes are made to the database (transaction rollback).
7. Exit codes are: 0 (success), 1 (validation), 2 (download), 3 (import).

### Test Scenarios
1. **Scenario**: Successful automatic sync
   - **Given**: PSA website is accessible and has the latest PSGC publication
   - **When**: User runs `ddev php artisan psgc:sync`
   - **Then**: Command downloads file, validates, imports, displays summary, exits with code 0

2. **Scenario**: Sync with manual file path
   - **Given**: User has a PSGC Excel file at `/local/PSGC.xlsx`
   - **When**: User runs `ddev php artisan psgc:sync --path=/local/PSGC.xlsx`
   - **Then**: Command uses the specified file, validates, imports, displays summary, exits with code 0

3. **Scenario**: Validation failure
   - **Given**: The downloaded Excel file is missing the required PSGC sheet
   - **When**: User runs `ddev php artisan psgc:sync`
   - **Then**: Command displays validation errors, no database changes, exits with code 1

4. **Scenario**: Website unavailable
   - **Given**: PSA website returns 500 or times out
   - **When**: User runs `ddev php artisan psgc:sync`
   - **Then**: Command displays an error message, no database changes, exits with code 2

5. **Scenario**: Import failure with rollback
   - **Given**: Excel file has invalid data causing an import exception
   - **When**: User runs `ddev php artisan psgc:sync`
   - **Then**: Command rolls back all database changes, displays error, exits with code 3

6. **Scenario**: Force import skips validation
   - **Given**: User has a PSGC file that would normally fail validation
   - **When**: User runs `ddev php artisan psgc:sync --path=/local/PSGC.xlsx --force`
   - **Then**: Command skips validation, imports data, displays summary, exits with code 0

---

## Open Questions & Risks

### Open Questions
1. **Question**: Should the command verify that the downloaded file size or hash matches PSA's metadata (if available)?
2. **Question**: Should the command retry failed downloads automatically (with backoff) or fail immediately?
3. **Question**: Should there be a rate limit for how often the command can be run (to prevent abuse)?
4. **Question**: Should the command send a notification (e.g., Slack, email) on successful sync completion?

### Known Risks
1. **Risk**: PSA website structure changes break HTML parsing logic
   - **Impact**: High
   - **Mitigation**: Implement flexible HTML parsing with multiple fallback patterns; log parsing errors for monitoring

2. **Risk**: Large files cause memory issues during download/parsing
   - **Impact**: Medium
   - **Mitigation**: Use streaming downloads where possible; implement memory limits with error handling

3. **Risk**: Network timeouts during download leave an incomplete file
   - **Impact**: Medium
   - **Mitigation**: Validate file integrity before import (check Excel structure, file size is reasonable)

4. **Risk**: Multiple sync commands running concurrently cause data corruption
   - **Impact**: Low
   - **Mitigation**: Implement file locking or database-level coordination (optional for now)

---

## Success Metrics

### Key Performance Indicators
- [Command Success Rate]: 95%+ of syncs complete without errors under normal conditions
- [Execution Time]: Full sync completes within 2 minutes under normal conditions
- [Data Accuracy]: Imported data matches the PSGC Excel file 100%
- [Error Clarity]: Users can identify and resolve errors from command output

### User Feedback
- [How will we gather feedback?]: Monitor command usage patterns, review error logs, gather feedback from GitHub issues or community discussions

---

## References
- **Mission**: `.references/mission.md`
- **Roadmap**: `.references/roadmap.md`
- **Design Patterns**: `.references/design-pattern.md`
- **Related Specs**: `2026-01-13-psa-crawler.md` (if created), `2026-01-13-data-import-process.md` (future)
- **External Resources**:
  - PSA PSGC Publications: https://psa.gov.ph/classification/psgc
  - Laravel Artisan Commands: https://laravel.com/docs/12.x/artisan
  - Laravel HTTP Client: https://laravel.com/docs/12.x/http-client

---

## Revision History

| Date | Author | Changes |
|-------|--------|---------|
| 2026-01-13 | Apollo Agent | Initial specification created |
