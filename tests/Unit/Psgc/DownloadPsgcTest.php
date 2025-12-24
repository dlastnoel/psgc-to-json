<?php

use App\Actions\Psgc\DownloadPsgc;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Http::fake();
    Storage::fake('local');
});

afterEach(function () {
    Storage::fake('local');
});

it('downloads file from valid PSA URL', function () {
    $excelContent = 'fake excel content';

    Http::fake([
        'https://psa.gov.ph/classification/psgc/PSGC-4Q-2025-Publication-Datafile.xlsx' => Http::response($excelContent, 200),
    ]);

    $downloader = new DownloadPsgc;
    $result = $downloader->execute('https://psa.gov.ph/classification/psgc/PSGC-4Q-2025-Publication-Datafile.xlsx');

    expect($result)->not->toBeNull();
    expect(Storage::disk('local')->exists('psgc/PSGC-4Q-2025-Publication-Datafile.xlsx'))->toBeTrue();
});

it('rejects non-HTTPS URLs', function () {
    $downloader = new DownloadPsgc;
    $result = $downloader->execute('http://example.com/PSGC.xlsx');

    expect($result)->toBeNull();
});

it('rejects URLs from non-PSA domains', function () {
    $downloader = new DownloadPsgc;
    $result = $downloader->execute('https://example.com/PSGC.xlsx');

    expect($result)->toBeNull();
});

it('validates file extension', function () {
    $htmlContent = '<html>not an excel</html>';

    Http::fake([
        'https://psa.gov.ph/classification/psgc/PSGC.txt' => Http::response($htmlContent, 200),
    ]);

    $downloader = new DownloadPsgc;
    $result = $downloader->execute('https://psa.gov.ph/classification/psgc/PSGC.txt');

    expect($result)->toBeNull();
});

it('validates minimum file size', function () {
    $smallContent = 'x'; // Too small to be valid Excel

    Http::fake([
        'https://psa.gov.ph/classification/psgc/PSGC.xlsx' => Http::response($smallContent, 200),
    ]);

    $downloader = new DownloadPsgc;
    $result = $downloader->execute('https://psa.gov.ph/classification/psgc/PSGC.xlsx');

    expect($result)->toBeNull();
});

it('handles HTTP errors gracefully', function () {
    Http::fake([
        'https://psa.gov.ph/classification/psgc/PSGC.xlsx' => Http::response('Not Found', 404),
    ]);

    $downloader = new DownloadPsgc;
    $result = $downloader->execute('https://psa.gov.ph/classification/psgc/PSGC.xlsx');

    expect($result)->toBeNull();
});

it('handles download timeout', function () {
    Http::fake(function () {
        throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
    });

    $downloader = new DownloadPsgc;
    $result = $downloader->execute('https://psa.gov.ph/classification/psgc/PSGC.xlsx');

    expect($result)->toBeNull();
});
