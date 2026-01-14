<?php

use App\Jobs\SyncPsgcJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
});

it('dispatches job when --queue option is used', function () {
    $testFile = storage_path('app/test.xlsx');

    $this->artisan('psgc:sync', ['--queue' => true, '--path' => $testFile])
        ->expectsOutput('Dispatching PSGC sync to queue...')
        ->expectsOutput('Job dispatched successfully!')
        ->assertExitCode(0);

    Queue::assertPushed(SyncPsgcJob::class);
});

it('does not dispatch job when --queue option is not used', function () {
    Queue::fake();

    $testFile = storage_path('app/test.xlsx');

    $this->artisan('psgc:sync', ['--path' => $testFile])
        ->assertExitCode(2); // File does not exist

    Queue::assertNothingPushed();
});

it('passes correct parameters to queued job', function () {
    $testFile = storage_path('app/test.xlsx');
    $force = true;

    $this->artisan('psgc:sync', [
        '--queue' => true,
        '--path' => $testFile,
        '--force' => $force,
    ])
        ->assertExitCode(0);

    Queue::assertPushed(SyncPsgcJob::class, function ($job) use ($testFile, $force) {
        return $job->path === $testFile && $job->force === $force;
    });
});

it('passes null path when --queue without --path', function () {
    $this->artisan('psgc:sync', ['--queue' => true])
        ->assertExitCode(0);

    Queue::assertPushed(SyncPsgcJob::class, function ($job) {
        return $job->path === null;
    });
});
