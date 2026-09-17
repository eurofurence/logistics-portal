<?php

use App\Console\Commands\RestoreBackup;
use Illuminate\Support\Facades\Storage;

test('deletes filament exports from the configured disk only', function () {
    config(['filesystems.default' => 'configured-storage']);
    $configuredDisk = Storage::fake('configured-storage');
    $s3Disk = Storage::fake('s3');
    $configuredDisk->put('filament_exports/export.csv', 'configured export');
    $configuredDisk->put('documents/keep.txt', 'keep');
    $s3Disk->put('filament_exports/export.csv', 'other export');

    $this->artisan('exports:delete-filament-exports')
        ->expectsConfirmation('Are you sure?', 'yes')
        ->assertSuccessful();

    $configuredDisk->assertMissing('filament_exports/export.csv');
    $configuredDisk->assertExists('documents/keep.txt');
    $s3Disk->assertExists('filament_exports/export.csv');
});

test('preserves files when a restore source is inside the configured destination', function () {
    config(['filesystems.default' => 'configured-storage']);
    $disk = Storage::fake('configured-storage');
    $disk->put('restore/source.txt', 'backup source');
    $disk->put('documents/existing.txt', 'existing file');

    expect(fn () => app(RestoreBackup::class)->restoreBackupToS3($disk->path('restore')))
        ->toThrow(RuntimeException::class, 'The extracted backup must be outside the destination disk before restoring.');

    $disk->assertExists(['restore/source.txt', 'documents/existing.txt']);
});

test('preserves files when a local restore source does not exist', function () {
    config(['filesystems.default' => 'configured-storage']);
    $disk = Storage::fake('configured-storage');
    $disk->put('documents/existing.txt', 'existing file');

    expect(fn () => app(RestoreBackup::class)->restoreBackupToS3($disk->path('missing-source')))
        ->toThrow(RuntimeException::class, 'The backup source and destination must exist before restoring.');

    $disk->assertExists('documents/existing.txt');
});
