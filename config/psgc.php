<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PSA Publication URL
    |--------------------------------------------------------------------------
    |
    | The URL where PSGC publications are hosted on the Philippine Statistics
    | Authority website. Users will be directed to this URL to download
    | the latest PSGC Excel file.
    |
    */

    'psa_url' => env('PSGC_PSA_URL', 'https://psa.gov.ph/classification/psgc'),

    /*
    |--------------------------------------------------------------------------
    | Storage Path
    |--------------------------------------------------------------------------
    |
    | The directory where PSGC Excel files will be stored after validation.
    | This path is relative to the storage/app directory.
    |
    */

    'storage_path' => env('PSGC_STORAGE_PATH', 'psgc'),

    /*
    |--------------------------------------------------------------------------
    | Memory Limit (MB)
    |--------------------------------------------------------------------------
    |
    | Memory limit in MB for processing PSGC Excel files via queue.
    | Large PSGC files (43k+ rows) require significant memory.
    | Default: 512MB
    |
    */

    'memory_limit' => env('PSGC_MEMORY_LIMIT', 1024), // MB - 1GB default for large PSGC files

    /*
    |--------------------------------------------------------------------------
    | Filename Pattern
    |--------------------------------------------------------------------------
    |
    | Regex pattern for extracting version information from PSGC filenames.
    | Expected format: PSGC-{Quarter}-{Year}-Publication-Datafile.xlsx
    | Examples: PSGC-4Q-2025-Publication-Datafile.xlsx
    |
    */

    'filename_pattern' => '/^PSGC-(\dQ)-(\d{4})-Publication-Datafile\.xlsx$/i',

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | Configuration for validating PSGC Excel files.
    |
    */

    'validation' => [

        /*
         * The sheet name that must exist in the Excel file.
         * This is the core data sheet containing geographic units.
         */
        'required_sheet' => 'PSGC',

        /*
         * Column names that must exist in the PSGC sheet.
         * All other columns are considered optional.
         */
        'required_columns' => [
            '10-digit PSGC',
            'Name',
            'Correspondence Code',
            'Geographic Level',
        ],

        /*
         * Valid values for the Geographic Level column.
         * 'SubMun' is recognized as a valid level (specifically for Manila districts).
         * Comparison is case-insensitive.
         */
        'valid_geographic_levels' => [
            'Region',
            'Province',
            'City',
            'Municipality',
            'Barangay',
            'SubMun', // Districts (will be normalized in Phase 2)
        ],

        /*
         * Geographic level that indicates a district (for Manila/NCR special case).
         */
        'district_level' => 'SubMun',
    ],
];
