<?php

namespace App\Jobs;

use App\Actions\Psgc\CrawlPsgcWebsite;
use App\Actions\Psgc\DownloadPsgc;
use App\Actions\Psgc\ImportPsgcData;
use App\Actions\Psgc\ValidatePsgcExcel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncPsgcJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 600;

    protected ?string $path;

    protected int $memoryLimit; // Will be set from config

    protected bool $force;

    protected array $results = [];

    public function __construct(?string $path = null, bool $force = false)
    {
        $this->path = $path;
        $this->force = $force;
        $this->memoryLimit = (int) config('psgc.memory_limit', 1024);
    }

    protected function boot(): void
    {
        // Increase memory limit for this job
        ini_set('memory_limit', $this->memoryLimit.'M');

        Log::info('Memory limit set for PSGC sync job', [
            'limit' => $this->memoryLimit.'M',
            'current' => ini_get('memory_limit'),
        ]);
    }

    public function handle(): void
    {
        $this->boot();

        try {
            Log::info('Starting PSGC sync job', ['path' => $this->path, 'force' => $this->force]);

            $filePath = $this->downloadPhase();

            if ($filePath === null) {
                $this->fail(new \Exception('Download phase failed'));
                return;
            }

            $validationResult = $this->validationPhase($filePath);

            if (! $validationResult['success']) {
                $this->fail(new \Exception('Validation failed'));
                return;
            }

            DB::transaction(function () use ($filePath) {
                $importResult = $this->importPhase($filePath);

                if (! $importResult['success']) {
                    throw new \Exception('Import failed: ' . ($importResult['message'] ?? 'Unknown error'));
                }

                $this->results = $importResult;
                Log::info('PSGC sync completed successfully', $this->results);
            });
        } catch (\Exception $e) {
            Log::error('PSGC sync job failed', ['error' => $e->getMessage()]);
            $this->fail($e);
        }
    }

    protected function downloadPhase(): ?string
    {
        if ($this->path !== null) {
            if (! file_exists($this->path)) {
                Log::error('Local file not found', ['path' => $this->path]);

                return null;
            }

            return realpath($this->path);
        }

        $crawler = new CrawlPsgcWebsite;
        $downloadUrl = $crawler->execute();

        if ($downloadUrl === null) {
            Log::error('Failed to find latest PSGC publication URL');

            return null;
        }

        $downloader = new DownloadPsgc;
        $filePath = $downloader->execute($downloadUrl);

        if ($filePath === null) {
            return null;
        }

        return $filePath;
    }

    protected function validationPhase(string $filePath): array
    {
        if ($this->force) {
            Log::info('Skipping validation (force mode)');

            return ['success' => true];
        }

        $validationResult = ValidatePsgcExcel::run($filePath);

        if (! $validationResult->isValid()) {
            Log::error('Validation failed', ['errors' => $validationResult->getErrors()]);

            return ['success' => false];
        }

        Log::info('Validation passed');

        return ['success' => true];
    }

    protected function importPhase(string $filePath): array
    {
        $importer = new ImportPsgcData($filePath);
        $result = $importer->execute();

        if (! $result['success']) {
            return ['success' => false, 'message' => $result['message'] ?? 'Unknown import error'];
        }

        return $result;
    }

    public function getResults(): array
    {
        return $this->results;
    }
}
