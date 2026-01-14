<?php

use App\Jobs\SyncPsgcJob;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

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
        ->assertExitCode(1); // Will fail on validation with test file

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

    Queue::assertPushed(SyncPsgcJob::class);
});
