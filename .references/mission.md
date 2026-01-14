# Mission Statement

## PSA PSGC Data Processor

Creates a reliable, normalized, and easily consumable database of Philippine geographical codes by automatically crawling, processing, and standardizing PSGC publications from the PSA website—handling special cases like Metro Manila's district structure—so that developers can access accurate, up-to-date location data through JSON APIs for use in dropdown selections and offline applications.

---

## Context

The Philippine Statistics Authority (PSA) publishes PSGC (Philippine Standard Geographic Code) data in Excel format at https://psa.gov.ph/classification/psgc. This project aims to:

1. **Automate Data Acquisition**: Crawl and download the latest PSGC publication Excel file
2. **Normalize Data Structure**: Process and standardize the Excel data into a consistent database schema
3. **Handle Special Cases**: Properly handle Metro Manila's unique structure where districts are elevated to municipality level
4. **Enable Offline Access**: Store processed data in a database for offline use
5. **Simplify Data Access**: Expose data as JSON for easy integration in dropdown selections and applications
6. **Support Updates**: Provide an easy way to re-sync when PSGC releases new publications

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

### Why This Project Exists

Existing solutions like [johnreybacal/psgc-reader](https://github.com/johnreybacal/psgc-reader) and [EdeesonOpina/laravel-psgc-api](https://github.com/EdeesonOpina/laravel-psgc-api) exist but cannot be used according to this project's specific requirements. This project provides a fresh, tailored approach to PSGC data processing.
