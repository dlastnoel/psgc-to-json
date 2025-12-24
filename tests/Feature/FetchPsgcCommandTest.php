<?php

use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $publicPath = public_path('psgc.xlsx');
    if (file_exists($publicPath)) {
        unlink($publicPath);
    }
});

afterEach(function () {
    $publicPath = public_path('psgc.xlsx');
    if (file_exists($publicPath)) {
        unlink($publicPath);
    }
});

it('successfully fetches and saves PSGC Excel file', function () {
    $publicationPageHtml = '<html><body><a href="/publications/2024-psgc">PSGC Publication 2024</a></body></html>';
    $downloadPageHtml = '<html><body><a href="/files/psgc-summary.xlsx">Download PSGC Summary</a></body></html>';
    $excelContent = 'mock-excel-file-content';

    Http::fake([
        'psa.gov.ph/classification/psgc' => Http::response($publicationPageHtml, 200),
        'psa.gov.ph/publications/2024-psgc' => Http::response($downloadPageHtml, 200),
        'psa.gov.ph/files/psgc-summary.xlsx' => Http::response($excelContent, 200),
    ]);

    $this->artisan('psgc:fetch')
        ->expectsOutput('Fetching PSGC data from PSA website...')
        ->assertSuccessful();

    $publicPath = public_path('psgc.xlsx');

    expect(file_exists($publicPath))->toBeTrue();
    expect(file_get_contents($publicPath))->toBe($excelContent);
});

it('handles failure when publication page cannot be found', function () {
    Http::fake([
        'psa.gov.ph/classification/psgc' => Http::response('<html><body>No publications</body></html>', 200),
    ]);

    $this->artisan('psgc:fetch')
        ->expectsOutput('Failed to locate the latest PSGC publication.')
        ->assertFailed();
});

it('handles failure when Excel download link cannot be found', function () {
    $publicationPageHtml = '<html><body><a href="/publications/2024-psgc">PSGC Publication 2024</a></body></html>';

    Http::fake([
        'psa.gov.ph/classification/psgc' => Http::response($publicationPageHtml, 200),
        'psa.gov.ph/publications/2024-psgc' => Http::response('<html><body>No Excel files</body></html>', 200),
    ]);

    $this->artisan('psgc:fetch')
        ->expectsOutput('Failed to locate the Excel download link.')
        ->assertFailed();
});

it('overwrites existing file when fetching new data', function () {
    $publicPath = public_path('psgc.xlsx');
    file_put_contents($publicPath, 'old-content');

    $publicationPageHtml = '<html><body><a href="/publications/2024-psgc">PSGC Publication 2024</a></body></html>';
    $downloadPageHtml = '<html><body><a href="/files/psgc-summary.xlsx">Download PSGC Summary</a></body></html>';
    $newExcelContent = 'new-excel-file-content';

    Http::fake([
        'psa.gov.ph/classification/psgc' => Http::response($publicationPageHtml, 200),
        'psa.gov.ph/publications/2024-psgc' => Http::response($downloadPageHtml, 200),
        'psa.gov.ph/files/psgc-summary.xlsx' => Http::response($newExcelContent, 200),
    ]);

    $this->artisan('psgc:fetch')->assertSuccessful();

    expect(file_exists($publicPath))->toBeTrue();
    expect(file_get_contents($publicPath))->toBe($newExcelContent);
    expect(file_get_contents($publicPath))->not->toBe('old-content');
});

it('handles HTTP errors gracefully', function () {
    Http::fake([
        'psa.gov.ph/classification/psgc' => Http::response('', 500),
    ]);

    $this->artisan('psgc:fetch')
        ->expectsOutput('Failed to locate the latest PSGC publication.')
        ->assertFailed();
});
