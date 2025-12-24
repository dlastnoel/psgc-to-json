<?php

namespace App\Actions\Psgc;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CrawlPsgcWebsite
{
    protected string $psaUrl;

    protected string $downloadUrlPattern = '/PSGC-(\dQ)-(\d{4})-Publication-Datafile\.xlsx/i';

    public function __construct()
    {
        $this->psaUrl = config('psgc.psa_url', 'https://psa.gov.ph/classification/psgc');
    }

    /**
     * Scrape PSA website to find latest PSGC publication download URL.
     */
    public function execute(): ?string
    {
        try {
            $this->info('Fetching PSGC publications from PSA website...');

            $response = Http::timeout(30)->get($this->psaUrl);

            if (! $response->successful()) {
                $this->error('Failed to fetch PSA website. Status: '.$response->status());

                return null;
            }

            $html = $response->body();
            $downloadLinks = $this->extractDownloadLinks($html);

            if (empty($downloadLinks)) {
                $this->warn('No PSGC download links found on PSA website.');

                return null;
            }

            $latestUrl = $this->findLatestPublication($downloadLinks);

            if ($latestUrl === null) {
                $this->warn('Could not determine latest PSGC publication URL.');

                return null;
            }

            $this->success('Latest PSGC publication found: '.basename($latestUrl));

            return $latestUrl;
        } catch (\Exception $e) {
            $this->error('Error crawling PSA website: '.$e->getMessage());
            Log::error('PSGC crawl error: '.$e->getMessage(), ['exception' => $e]);

            return null;
        }
    }

    /**
     * Extract all PSGC download links from HTML.
     *
     * @return array<string, string> Array of URLs keyed by filename
     */
    protected function extractDownloadLinks(string $html): array
    {
        $links = [];

        // Look for Excel file links in anchor tags
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $html, $matches);

        foreach ($matches[1] as $url) {
            // Check if URL matches PSGC filename pattern
            if (preg_match($this->downloadUrlPattern, $url)) {
                // Convert relative URLs to absolute
                if (! str_starts_with($url, 'http')) {
                    $url = rtrim($this->psaUrl, '/').'/'.ltrim($url, '/');
                }

                $filename = basename($url);
                $links[$filename] = $url;
            }
        }

        return $links;
    }

    /**
     * Find the most recent publication from download links.
     *
     * @param  array<string, string>  $downloadLinks
     * @return string|null Absolute URL of latest publication
     */
    protected function findLatestPublication(array $downloadLinks): ?string
    {
        $latest = null;
        $latestYear = 0;
        $latestQuarter = 0;

        foreach ($downloadLinks as $filename => $url) {
            if (preg_match($this->downloadUrlPattern, $filename, $matches)) {
                $quarter = (int) $matches[1];
                $year = (int) $matches[2];

                // Compare with current latest
                if ($year > $latestYear || ($year === $latestYear && $quarter > $latestQuarter)) {
                    $latest = $url;
                    $latestYear = $year;
                    $latestQuarter = $quarter;
                }
            }
        }

        return $latest;
    }

    /**
     * Log info message.
     */
    protected function info(string $message): void
    {
        Log::info('[CrawlPsgcWebsite] '.$message);
    }

    /**
     * Log success message.
     */
    protected function success(string $message): void
    {
        Log::info('[CrawlPsgcWebsite] '.$message);
    }

    /**
     * Log warning message.
     */
    protected function warn(string $message): void
    {
        Log::warning('[CrawlPsgcWebsite] '.$message);
    }

    /**
     * Log error message.
     */
    protected function error(string $message): void
    {
        Log::error('[CrawlPsgcWebsite] '.$message);
    }
}
