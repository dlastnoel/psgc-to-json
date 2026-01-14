# PSGC Sync Command

The `psgc:sync` command supports both synchronous and asynchronous (queue-based) execution.

## Windows Setup

**Important**: On Windows, set `DB_CONNECTION=mysql` before running commands:

```powershell
$env:DB_CONNECTION='mysql'; php artisan psgc:sync --queue --path="storage/app/psgc/PSGC-4Q-2025-Publication-Datafile.xlsx"
```

See `WINDOWS_SETUP.md` for detailed Windows/MySQL configuration.

## Usage

### Synchronous Mode (Default)
```powershell
$env:DB_CONNECTION='mysql'; php artisan psgc:sync --path="path/to/file.xlsx"
```

### Queue Mode (Recommended for Large Files)
```powershell
$env:DB_CONNECTION='mysql'; php artisan psgc:sync --queue --path="path/to/file.xlsx"
$env:DB_CONNECTION='mysql'; php artisan queue:work --once --max-jobs=1
```

## Options

| Option | Description |
|--------|-------------|
| `--path` | Path to local PSGC Excel file |
| `--force` | Skip validation and force import |
| `--queue` | Run as background queue job (recommended for large files) |

## Queue Mode Benefits

1. **Memory Isolation**: Jobs run in separate processes with dedicated memory
2. **Non-blocking**: Command returns immediately after dispatching
3. **Retry Logic**: Failed jobs are automatically retried (up to 3 attempts)
4. **Error Tracking**: Failed jobs are logged and can be inspected
5. **Timeout Protection**: Jobs timeout after 10 minutes (600 seconds)
6. **Automatic Memory Increase**: Queue jobs automatically increase memory to 256MB

## Memory Management

The PSGC Excel files are large (40k+ rows) and require significant memory to process. The queue mode automatically handles this:

- **Synchronous mode**: Uses default PHP memory limit (usually 128MB) - **will fail on large files**
- **Queue mode**: Automatically increases to 256MB - **works for all PSGC files**

If you still encounter memory issues:

### Option 1: Increase queue worker memory
```bash
php -d memory_limit=1G artisan queue:work
```

### Option 2: Increase memory limit in `php.ini`
```ini
memory_limit = 512M
```

### Option 3: Increase memory limit per command
```bash
# Linux/Mac
php -d memory_limit=1G artisan psgc:sync --queue

# Windows (PowerShell)
$env:PHP_MEMORY_LIMIT="1G"; php artisan psgc:sync --queue
```

## Monitoring Queued Jobs

### Process Queue
```bash
php artisan queue:work
```

### Process One Job
```bash
php artisan queue:work --once --max-jobs=1
```

### Check Failed Jobs
```bash
php artisan queue:failed
```

### Retry Failed Jobs
```bash
php artisan queue:retry all
```

### View Job Details
```bash
php artisan queue:failed-table
```

## Logs

Detailed job execution logs are available in `storage/logs/laravel.log`.

## Example Workflow

```bash
# Dispatch job to queue
php artisan psgc:sync --queue --path="PSGC-4Q-2025-Publication-Datafile.xlsx"

# In another terminal, process queue (memory automatically handled)
php artisan queue:work

# Monitor logs
tail -f storage/logs/laravel.log
```

## Import Results

Successful imports will process approximately:
- 18 Regions
- 82 Provinces
- 1,600+ Cities/Municipalities
- 42,000+ Barangays

## Troubleshooting

### Job fails with "Allowed memory size exhausted"
- Use queue mode instead of synchronous mode
- Increase memory limit for queue worker: `php -d memory_limit=1G artisan queue:work`

### Job stays in "RUNNING" state
- Check Laravel logs for errors: `tail -f storage/logs/laravel.log`
- Verify file path exists and is readable

### Validation fails
- Ensure file is a valid Excel file (.xlsx)
- Check that "PSGC" sheet exists in the file
- Verify required columns are present: `10-digit PSGC`, `Name`, `Geographic Level`, `Correspondence Code`

