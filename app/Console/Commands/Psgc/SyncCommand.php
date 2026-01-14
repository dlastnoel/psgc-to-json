<?php

namespace App\Console\Commands\Psgc;

use App\Actions\Psgc\CrawlPsgcWebsite;
use App\Actions\Psgc\DownloadPsgc;
use App\Actions\Psgc\ImportPsgcData;
use App\Actions\Psgc\ValidatePsgcExcel;
use App\Jobs\SyncPsgcJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'psgc:sync
                            {--path= : Path to local PSGC Excel file}
                            {--force : Skip validation and force import}
                            {--queue : Run as background queue job (recommended for large files)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync PSGC data by downloading, validating, and importing the latest Excel file from PSA website';

    protected string $storagePath = 'psgc';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('queue')) {
            return $this->handleQueued();
        }

        return $this->handleSynchronous();
    }

    /**
     * Handle as queued job (recommended for large files).
     */
    protected function handleQueued(): int
    {
        $this->info('Dispatching PSGC sync to queue...');

        SyncPsgcJob::dispatch(
            $this->option('path') ?: null,
            $this->option('force')
        );

        $this->newLine();
        $this->success('Job dispatched successfully!');
        $this->newLine();
        $this->line('To monitor the job:');
        $this->line('  php artisan queue:work');
        $this->newLine();
        $this->line('To check job status:');
        $this->line('  php artisan queue:failed');
        $this->newLine();
        $this->warn('Note: Check storage/logs/laravel.log for detailed progress');

        return 0;
    }

    /**
     * Handle synchronously (default).
     */
    protected function handleSynchronous(): int
    {
        return DB::transaction(function () {
            try {
                // Phase 1: Download
                $filePath = $this->downloadPhase();

                if ($filePath === null) {
                    return 2; // Download failed
                }

                // Phase 2: Validate
                $validationResult = $this->validationPhase($filePath);

                if (! $validationResult['success']) {
                    return 1; // Validation failed
                }

                // Phase 3: Import
                $importResult = $this->importPhase($filePath);

                if (! $importResult['success']) {
                    return 3; // Import failed
                }

                // Success
                return 0;
            } catch (\Exception $e) {
                $this->error('Unexpected error during sync: '.$e->getMessage());

                return 1;
            }
        });
    }

    /**
     * Download phase: Crawl website or use local file.
     */
    protected function downloadPhase(): ?string
    {
        $this->info('Phase 1: Downloading PSGC file...');

        $path = $this->option('path');

        if ($path !== null) {
            // Use local file
            $this->info('Using local file: '.$path);

            if (! file_exists($path)) {
                $this->error('Local file not found: '.$path);

                return null;
            }

            return realpath($path);
        }

        // Automatic download
        $this->info('Crawling PSA website for latest publication...');

        $crawler = new CrawlPsgcWebsite;
        $downloadUrl = $crawler->execute();

        if ($downloadUrl === null) {
            $this->error('Failed to find latest PSGC publication URL.');
            $this->displayUrlAndInstructions();

            return null;
        }

        $downloader = new DownloadPsgc;
        $filePath = $downloader->execute($downloadUrl);

        if ($filePath === null) {
            return null;
        }

        $this->success('Download completed successfully.');

        return $filePath;
    }

    /**
     * Validation phase: Check Excel file structure.
     */
    protected function validationPhase(string $filePath): array
    {
        $this->newLine();
        $this->info('Phase 2: Validating PSGC file...');

        if ($this->option('force')) {
            $this->warn('Skipping validation (--force flag is set)');

            return ['success' => true];
        }

        $validationResult = ValidatePsgcExcel::run($filePath);

        if (! $validationResult->isValid()) {
            $this->error('Validation failed:');
            foreach ($validationResult->getErrors() as $error) {
                $this->line('  - '.$error);
            }

            return ['success' => false];
        }

        $this->success('Validation passed:');
        $this->line('  - PSGC sheet found');
        $this->line('  - Required columns present');

        return ['success' => true];
    }

    /**
     * Import phase: Parse and import data to database.
     */
    protected function importPhase(string $filePath): array
    {
        $this->newLine();
        $this->info('Phase 3: Importing PSGC data...');

        $importer = new ImportPsgcData($filePath);
        $result = $importer->execute();

        if (! $result['success']) {
            $this->error('Import failed: '.$result['message']);

            return ['success' => false, 'message' => $result['message']];
        }

        $this->success('Import completed successfully.');
        $this->newLine();

        $this->displayImportSummary($result);

        return ['success' => true, 'data' => $result];
    }

    /**
     * Display import summary.
     */
    protected function displayImportSummary(array $result): void
    {
        $this->info('Import Summary:');
        $this->line('  Regions: '.$result['regions']);
        $this->line('  Provinces: '.$result['provinces']);
        $this->line('  Cities/Municipalities: '.$result['cities_municipalities']);
        $this->line('  Barangays: '.$result['barangays']);

        $versionInfo = $this->extractVersion($this->option('path') ?? '');
        if ($versionInfo['quarter'] !== 'Unknown') {
            $this->newLine();
            $this->info('Imported Version: '.$versionInfo['quarter'].' '.$versionInfo['year']);
        }
    }

    /**
     * Display success message (alias for info with green color).
     */
    protected function success(string $message): void
    {
        $this->line("<fg=green>$message</>");
    }

    /**
     * Display URL and instructions for manual download.
     */
    protected function displayUrlAndInstructions(): void
    {
        $url = config('psgc.psa_url');

        $this->newLine();
        $this->line('Download PSGC Publications from: <comment>'.$url.'</comment>');
        $this->newLine();
        $this->line('Instructions:');
        $this->line('1. Navigate to the URL above');
        $this->line('2. Find the "Download PSGC Publications" section');
        $this->line('3. Click on the latest publication link');
        $this->line('4. Download the Excel file');
        $this->line('5. Provide the file path to this command using the --path argument');
        $this->newLine();
        $this->line('Or simply place the downloaded file in your storage/app/psgc/ directory.');
        $this->newLine();
    }

    /**
     * Extract version information from filename or file path.
     */
    protected function extractVersion(string $filePath): array
    {
        if ($filePath === '') {
            return [
                'quarter' => 'Unknown',
                'year' => 'Unknown',
            ];
        }

        $filename = basename($filePath);
        $pattern = config('psgc.filename_pattern');

        if (! preg_match($pattern, $filename, $matches)) {
            return [
                'quarter' => 'Unknown',
                'year' => 'Unknown',
            ];
        }

        return [
            'quarter' => $matches[1],
            'year' => $matches[2],
        ];
    }
}
