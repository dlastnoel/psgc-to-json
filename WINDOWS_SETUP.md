# PSGC Sync Command - Windows & MySQL Setup

## Database Configuration (Windows)

### Issue: Environment Variable Override
On Windows, there might be a system environment variable `DB_CONNECTION` set to `sqlite` that overrides the `.env` file.

### Solution 1: Remove System Environment Variable
1. Press Win+R, type `sysdm.cpl`, press Enter
2. Go to Advanced → Environment Variables
3. Look for `DB_CONNECTION` in User/System variables
4. Delete it or change it to `mysql`

### Solution 2: Use Wrapper Script (Recommended)
Use the provided batch file that sets the environment variable before running commands:

```bash
# Use the wrapper script
artisan-mysql.bat psgc:sync --queue --path="storage/app/psgc/PSGC-4Q-2025-Publication-Datafile.xlsx"

# Or use PowerShell
$env:DB_CONNECTION='mysql'; php artisan psgc:sync --queue --path="storage/app/psgc/PSGC-4Q-2025-Publication-Datafile.xlsx"
```

### Solution 3: Set Variable in PowerShell Session
```powershell
$env:DB_CONNECTION='mysql'
php artisan psgc:sync --queue --path="storage/app/psgc/PSGC-4Q-2025-Publication-Datafile.xlsx"
```

## MySQL Database Setup

### 1. Create Database
```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS psa_psgc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 2. Update .env File
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=psa_psgc
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 3. Run Migrations
```bash
# Clear config cache first
php artisan config:clear

# Run migrations (use PowerShell to set DB_CONNECTION)
$env:DB_CONNECTION='mysql'; php artisan migrate:fresh
```

## Usage

### Queue Mode (Recommended for Large Files)
```powershell
$env:DB_CONNECTION='mysql'; php artisan psgc:sync --queue --path="storage/app/psgc/PSGC-4Q-2025-Publication-Datafile.xlsx"
$env:DB_CONNECTION='mysql'; php artisan queue:work --once --max-jobs=1
```

### Synchronous Mode (Small Files)
```powershell
$env:DB_CONNECTION='mysql'; php artisan psgc:sync --path="file.xlsx"
```

## Memory Management

The queue mode automatically sets memory limit to 1GB (configurable via `psgc.memory_limit` in config/psgc.php).

### Monitoring Progress
```bash
# Check Laravel logs
Get-Content storage/logs/laravel.log -Tail 20

# Check imported records
$env:DB_CONNECTION='mysql'; php check-import.php

# Check jobs in queue
$env:DB_CONNECTION='mysql'; php check-jobs.php
```

## Import Performance with MySQL

Expected processing time for full PSGC file (43k+ rows):
- **Validation**: ~5 seconds (first 100 rows only)
- **Parsing**: ~25 seconds (all 43,769 rows)
- **Import**: 2-5 minutes (42,000+ inserts with relationships)

### Total Time: 3-6 minutes

### MySQL Optimization
For better performance, ensure:

1. **InnoDB Buffer Pool Size** (in `my.ini`):
   ```ini
   innodb_buffer_pool_size = 256M
   innodb_log_file_size = 64M
   ```

2. **Disable Strict Mode** (if needed):
   ```env
   DB_STRICT_MODE=false
   ```

## Troubleshooting

### Error: "DB_CONNECTION is sqlite"
- Use PowerShell wrapper: `$env:DB_CONNECTION='mysql'; php artisan ...`
- Or run: `artisan-mysql.bat ...`

### Error: "Access denied for user 'root'"
- Check DB_PASSWORD in .env file
- Verify MySQL user has CREATE DATABASE privilege

### Error: "Database does not exist"
- Run: `mysql -u root -p -e "CREATE DATABASE psa_psgc;"`
- Check DB_DATABASE in .env file

### Job running long time
- Normal for 43k rows (3-6 minutes)
- Check MySQL processes: `mysql -u root -p -e "SHOW PROCESSLIST;"`
- Check Laravel logs for progress updates

## Helper Scripts

The following helper scripts are provided:

- `artisan-mysql.bat` - Windows batch file wrapper
- `check-jobs.php` - Check jobs in queue
- `check-import.php` - Check imported record counts
