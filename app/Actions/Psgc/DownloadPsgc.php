<?php

namespace App\Actions\Psgc;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DownloadPsgc
{
    protected string $storagePath = 'psgc';

    protected string $allowedDomain = 'psa.gov.ph';

    protected array $allowedExtensions = ['xlsx', 'xls'];

    public function __construct()
    {
        //
    }

    /**
     * Download PSGC Excel file from given URL to storage.
     *
     * @return string|null Absolute file path on success, null on failure
     */
    public function execute(string $url): ?string
    {
        try {
            $this->info('Downloading PSGC file from: '.$url);

            // Validate URL
            if (! $this->isValidUrl($url)) {
                $this->error('Invalid download URL. URL must be HTTPS and from psa.gov.ph domain.');

                return null;
            }

            // Download to temporary location
            $tempPath = $this->downloadFile($url);

            if ($tempPath === null) {
                return null;
            }

            // Validate file is a valid Excel
            if (! $this->isValidExcelFile($tempPath)) {
                $this->error('Downloaded file is not a valid Excel file.');

                $this->cleanup($tempPath);

                return null;
            }

            // Move to final storage location
            $filename = basename(parse_url($url, PHP_URL_PATH));
            $finalPath = $this->storagePath.'/'.$filename;

            Storage::disk('local')->put($finalPath, Storage::disk('local')->get($tempPath));

            // Cleanup temporary file
            $this->cleanup($tempPath);

            $absolutePath = storage_path('app/'.$finalPath);

            $this->success('PSGC file downloaded and saved to: '.$absolutePath);

            return $absolutePath;
        } catch (\Exception $e) {
            $this->error('Error downloading PSGC file: '.$e->getMessage());
            Log::error('PSGC download error', [
                'url' => $url,
                'exception' => $e,
            ]);

            return null;
        }
    }

    /**
     * Validate URL is HTTPS and from allowed domain.
     */
    protected function isValidUrl(string $url): bool
    {
        $parsed = parse_url($url);

        if ($parsed === false) {
            return false;
        }

        $scheme = $parsed['scheme'] ?? '';
        $host = $parsed['host'] ?? '';

        return strtolower($scheme) === 'https' &&
               Str::endsWith(strtolower($host), $this->allowedDomain);
    }

    /**
     * Download file from URL to temporary storage.
     */
    protected function downloadFile(string $url): ?string
    {
        try {
            $response = Http::timeout(60)->get($url);

            if (! $response->successful()) {
                $this->error('Failed to download file. Status: '.$response->status());

                return null;
            }

            $tempFilename = 'psgc_'.time().'_'.Str::random(8).'.tmp';
            Storage::disk('local')->put($this->storagePath.'/'.$tempFilename, $response->body());

            return $this->storagePath.'/'.$tempFilename;
        } catch (\Exception $e) {
            $this->error('Download failed: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Validate file is a valid Excel file.
     */
    protected function isValidExcelFile(string $storagePath): bool
    {
        $fullPath = storage_path('app/'.$storagePath);

        // Check file exists
        if (! file_exists($fullPath)) {
            return false;
        }

        // Check extension
        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        if (! in_array($extension, $this->allowedExtensions, true)) {
            return false;
        }

        // Check file size (should be at least 1KB)
        $fileSize = filesize($fullPath);

        if ($fileSize < 1024) {
            $this->warn('Downloaded file is too small ('.$fileSize.' bytes). May be corrupted.');

            return false;
        }

        return true;
    }

    /**
     * Clean up temporary file.
     */
    protected function cleanup(string $storagePath): void
    {
        $fullPath = storage_path('app/'.$storagePath);

        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }

    /**
     * Log info message.
     */
    protected function info(string $message): void
    {
        Log::info('[DownloadPsgc] '.$message);
    }

    /**
     * Log success message.
     */
    protected function success(string $message): void
    {
        Log::info('[DownloadPsgc] '.$message);
    }

    /**
     * Log warning message.
     */
    protected function warn(string $message): void
    {
        Log::warning('[DownloadPsgc] '.$message);
    }

    /**
     * Log error message.
     */
    protected function error(string $message): void
    {
        Log::error('[DownloadPsgc] '.$message);
    }
}
