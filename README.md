# PSGC to JSON - Philippines Geographic Standard Code Manager

Laravel application for syncing PSGC data from PSA website and managing Philippine geographic codes.

## Features

- **Auto-download**: Fetch latest PSGC Excel file from PSA website
- **Validation**: Verify file format and structure
- **Import**: Load 43k+ records into MySQL database
- **Queue Processing**: Handle large files asynchronously
- **Memory Management**: Automatic memory optimization (1GB for queue jobs)
- **Error Tracking**: Failed jobs logging and retry mechanism

## Database Support

### MySQL (Recommended for Production)
- Better performance for large datasets
- Concurrent connection support
- Transaction support

### SQLite (Development Only)
- Simple file-based storage
- No server required
- **Not recommended for large PSGC files**

## Quick Start (Windows with MySQL)

### 1. Setup MySQL Database
```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS psa_psgc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 2. Configure Application
Copy `.env.example` to `.env` and update:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=psa_psgc
DB_USERNAME=root
DB_PASSWORD=your_password
```

**Important**: Windows may have `DB_CONNECTION=sqlite` as a system environment variable. Use PowerShell wrapper:

```powershell
$env:DB_CONNECTION='mysql'; php artisan ...
```

### 3. Install Dependencies
```bash
composer install
```

### 4. Run Migrations
```powershell
$env:DB_CONNECTION='mysql'; php artisan migrate
```

### 5. Sync PSGC Data

**Queue Mode (Recommended):**
```powershell
$env:DB_CONNECTION='mysql'; php artisan psgc:sync --queue --path="storage/app/psgc/PSGC-4Q-2025-Publication-Datafile.xlsx"
$env:DB_CONNECTION='mysql'; php artisan queue:work --once --max-jobs=1
```

**Synchronous Mode:**
```powershell
$env:DB_CONNECTION='mysql'; php artisan psgc:sync --path="storage/app/psgc/PSGC-4Q-2025-Publication-Datafile.xlsx"
```

## Data Model

```
Region (18)
├── Province (82)
│   ├── City Municipality (1,656)
│   │   └── Barangay (42,011)
│   └── Barangay (direct, rare)
└── Barangay (NCR only, special case)
```

## Memory Requirements

| Operation | Memory | Time |
|------------|---------|-------|
| Validation | 256MB | ~5s |
| Parsing (43k rows) | 512MB | ~30s |
| Import (MySQL) | 1GB | 5-8m |

**Queue mode automatically sets memory to 1GB.**

## Commands

### PSGC Sync
```bash
# Download and validate only
php artisan psgc:sync

# Use local file
php artisan psgc:sync --path="file.xlsx"

# Force skip validation
php artisan psgc:sync --path="file.xlsx" --force

# Queue mode (background)
php artisan psgc:sync --queue --path="file.xlsx"
```

### Queue Management
```bash
# Process queue continuously
php artisan queue:work

# Process one job
php artisan queue:work --once --max-jobs=1

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### Database
```bash
# Run migrations
php artisan migrate

# Fresh install (drops all tables)
php artisan migrate:fresh

# Reset database (roll back and migrate)
php artisan migrate:refresh
```

## Configuration

### PSGC Settings (`config/psgc.php`)

```php
'psa_url' => env('PSGC_PSA_URL', 'https://psa.gov.ph/classification/psgc'),
'storage_path' => env('PSGC_STORAGE_PATH', 'psgc'),
'memory_limit' => env('PSGC_MEMORY_LIMIT', 1024), // MB
```

### Update Memory Limit

**Option 1: Environment Variable**
```env
PSGC_MEMORY_LIMIT=2048
```

**Option 2: Edit Config**
```php
'memory_limit' => 2048,
```

**Option 3: Command Line**
```powershell
$env:PSGC_MEMORY_LIMIT=2048; php artisan psgc:sync --queue
```

## Helper Scripts

Windows batch file for MySQL:
```bash
# Instead of: php artisan ...
# Use: artisan-mysql.bat ...
```

Helper scripts:
- `check-jobs.php` - Count jobs in queue
- `check-import.php` - Show imported record counts
- `artisan-mysql.bat` - Wrapper with DB_CONNECTION=mysql

## Troubleshooting

### Error: "DB_CONNECTION is sqlite"
**Cause**: Windows system environment variable override

**Solution**:
```powershell
$env:DB_CONNECTION='mysql'; php artisan ...
```

### Error: "Allowed memory size exhausted"
**Cause**: Large PSGC file with default 128MB limit

**Solution**: Use queue mode (auto 1GB):
```powershell
$env:DB_CONNECTION='mysql'; php artisan psgc:sync --queue ...
```

### Error: "Database file does not exist" (SQLite)
**Cause**: Using SQLite with wrong database path

**Solution**: Switch to MySQL (recommended) or fix path:
```env
DB_DATABASE=database/database.sqlite
```

### Error: "Access denied for user 'root'"
**Cause**: Wrong password in .env

**Solution**: Update DB_PASSWORD in .env file

### Job stuck in "RUNNING" state
**Cause**: Processing 43k+ rows takes time (5-8 minutes)

**Check Progress**:
```powershell
Get-Content storage/logs/laravel.log -Tail 20
```

**Check MySQL Activity**:
```bash
mysql -u root -p -e "SHOW PROCESSLIST;"
```

## Import Results

Successful import processes:
- **Regions**: 18
- **Provinces**: 82
- **Cities/Municipalities**: 1,656
- **Barangays**: 42,011
- **Total Records**: 43,767
- **Processing Time**: 3-8 minutes

## Development

### Running Tests
```powershell
$env:DB_CONNECTION='mysql'; php artisan test
```

### Code Structure
```
app/
├── Actions/Psgc/          # Business logic
├── Jobs/SyncPsgcJob.php # Queue job
├── Console/Commands/Psgc/ # Artisan commands
└── Models/               # Eloquent models
```

## Documentation

- `QUEUE_USAGE.md` - Detailed queue mode usage
- `WINDOWS_SETUP.md` - Windows & MySQL setup guide

## License

MIT License
