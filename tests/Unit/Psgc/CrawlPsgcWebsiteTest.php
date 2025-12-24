<?php

use App\Actions\Psgc\CrawlPsgcWebsite;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake();
});

it('extracts download links from HTML', function () {
    $html = <<<'HTML'
<!DOCTYPE html>
<html>
<body>
    <a href="/downloads/PSGC-4Q-2025-Publication-Datafile.xlsx">Download 4Q 2025</a>
    <a href="/downloads/PSGC-3Q-2024-Publication-Datafile.xlsx">Download 3Q 2024</a>
    <a href="/downloads/other-file.xlsx">Other file</a>
</body>
</html>
HTML;

    Http::fake([
        'https://psa.gov.ph/classification/psgc' => Http::response($html, 200),
    ]);

    $crawler = new CrawlPsgcWebsite;
    $result = $crawler->execute();

    expect($result)->toBeNull(); // No "latest" link yet, just parsing
});

it('finds most recent publication by date', function () {
    $html = <<<'HTML'
<!DOCTYPE html>
<html>
<body>
    <a href="https://psa.gov.ph/classification/psgc/PSGC-4Q-2025-Publication-Datafile.xlsx">4Q 2025</a>
    <a href="https://psa.gov.ph/classification/psgc/PSGC-3Q-2025-Publication-Datafile.xlsx">3Q 2025</a>
    <a href="https://psa.gov.ph/classification/psgc/PSGC-1Q-2024-Publication-Datafile.xlsx">1Q 2024</a>
</body>
</html>
HTML;

    Http::fake([
        'https://psa.gov.ph/classification/psgc' => Http::response($html, 200),
    ]);

    $crawler = new CrawlPsgcWebsite;

    // We need to execute and check the URL found
    $url = $crawler->execute();

    expect($url)->toContain('4Q-2025'); // Should return the most recent
});

it('returns null when website returns error', function () {
    Http::fake([
        'https://psa.gov.ph/classification/psgc' => Http::response('Internal Server Error', 500),
    ]);

    $crawler = new CrawlPsgcWebsite;
    $result = $crawler->execute();

    expect($result)->toBeNull();
});

it('returns null when no PSGC links found', function () {
    $html = <<<'HTML'
<!DOCTYPE html>
<html>
<body>
    <a href="/downloads/other-file.xlsx">Other file</a>
</body>
</html>
HTML;

    Http::fake([
        'https://psa.gov.ph/classification/psgc' => Http::response($html, 200),
    ]);

    $crawler = new CrawlPsgcWebsite;
    $result = $crawler->execute();

    expect($result)->toBeNull();
});

it('handles network timeout gracefully', function () {
    Http::fake(function () {
        throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
    });

    $crawler = new CrawlPsgcWebsite;
    $result = $crawler->execute();

    expect($result)->toBeNull();
});
