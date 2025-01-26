<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Backup\Config\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Spatie\Backup\Tasks\Backup\BackupJobFactory;

class BackupWithS3 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:with-s3';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inserts files from S3 into the local backup and then executes the backup';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Start the backup and add S3 files with folder structure...');

        File::deleteDirectory(storage_path('app/backup-s3'));

        // 1. Download the files from S3
        $s3Files = Storage::disk('s3')->allFiles(''); // Get all files including path
        if (empty($s3Files)) {
            $this->info('No files found on S3');
        } else {
            $localBackupPath = storage_path('app/backup-s3'); // Temporary storage location
            File::ensureDirectoryExists($localBackupPath);

            foreach ($s3Files as $file) {
                $this->info("Download file {$file} from S3...");
                $contents = Storage::disk('s3')->get($file);

                // Retain the folder structure
                $localFilePath = "{$localBackupPath}/{$file}";
                File::ensureDirectoryExists(dirname($localFilePath)); // Create the directories
                File::put($localFilePath, $contents);
            }

            $this->info('S3 files were downloaded locally and the folder structure was retained');
        }

        // 2. Load the backup configuration as a Config object
        $backupConfig = Config::fromArray(config('backup'));

        // 3. Create a backup with a spatie
        $this->info('Create the backup with local S3 files...');
        $backupJob = BackupJobFactory::createFromConfig($backupConfig);
        $backupJob->run();

        // 4. Delete temporary local S3 files
        File::deleteDirectory(storage_path('app/backup-s3'));

        $this->info('Backup completed');
    }
}
