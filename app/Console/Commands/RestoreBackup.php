<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\LocalFilesystemAdapter;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class RestoreBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:spatie-restore
                        {password : Password for the encrypted backup}
                        {--only-download : Only download and extract the backup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore a Spatie backup from a specified file.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $onlyDownload = $this->option('only-download');

        if ($onlyDownload) {
            $this->info('Only downloading and extracting the backup...');
        }

        $sftpDisk = Storage::disk('sftp');
        $temporaryDirectory = storage_path('framework/cache/backup-restore-temp');

        $files = collect($sftpDisk->files(config('app.name')));

        if ($files->isEmpty()) {
            $this->warn('No files found on the SFTP server');

            return Command::SUCCESS;
        }

        // Find the latest file based on the modification date
        $latestFile = $files->sortByDesc(function ($file) use ($sftpDisk) {
            return $sftpDisk->lastModified($file);
        })->first();

        $this->info("Latest backup: $latestFile\n");

        if (! $this->confirm('Do you want to restore this backup?')) {
            return Command::FAILURE;
        }

        File::deleteDirectory($temporaryDirectory);
        File::ensureDirectoryExists($temporaryDirectory);

        $extension = pathinfo($latestFile, PATHINFO_EXTENSION);

        // Download the file from the SFTP server and save it locally
        $content = $sftpDisk->get($latestFile);
        File::put($temporaryDirectory.'/restore.'.$extension, $content);

        // Unzip the file
        $archiveFullPath = $temporaryDirectory.'/restore.'.$extension;
        $extractTo = $temporaryDirectory.'/extracted/';
        $password = $this->argument('password');

        if ($this->extractEncryptedArchive($archiveFullPath, $extractTo, $password) !== Command::SUCCESS) {
            return Command::FAILURE;
        }

        if ($onlyDownload) {
            return Command::SUCCESS;
        }

        // Restore the backup by uploading extracted files to S3
        $this->restoreBackupToS3($extractTo);

        File::deleteDirectory($temporaryDirectory);
        $this->info('Temporary files were deleted');
        $this->info('Finished');

        return Command::SUCCESS;
    }

    public function extractEncryptedArchive($archivePath, $extractTo, $password): int
    {
        $zip = new ZipArchive;

        $this->info($archivePath);

        if (! file_exists($archivePath)) {
            $this->error("The file does not exist at the specified path: {$archivePath}");

            return Command::FAILURE;
        }

        if ($zip->open($archivePath) === true) {
            // Set the password
            if (! $zip->setPassword($password)) {
                $this->error('Error when setting the password');

                return Command::FAILURE;
            }

            // Check whether the ZIP file is encrypted
            if (! $zip->extractTo($extractTo)) {
                $this->error('Error when unpacking the archive. Check the password');

                return Command::FAILURE;
            }

            $zip->close();
            $this->info("Archive was successfully extracted to '{$extractTo}'");

            return Command::SUCCESS;
        } else {
            $this->error('Error when opening the archive');

            return Command::FAILURE;
        }

        return Command::FAILURE;
    }

    public function restoreBackupToS3($extractTo): void
    {
        $s3Disk = Storage::disk();

        if ($s3Disk instanceof LocalFilesystemAdapter) {
            $diskRoot = realpath($s3Disk->path(''));
            $sourcePath = realpath($extractTo);

            if ($diskRoot === false || $sourcePath === false) {
                throw new \RuntimeException('The backup source and destination must exist before restoring.');
            }

            $diskRoot = rtrim(str_replace('\\', '/', $diskRoot), '/').'/';
            $sourcePath = rtrim(str_replace('\\', '/', $sourcePath), '/').'/';

            if (str_starts_with($sourcePath, $diskRoot)) {
                throw new \RuntimeException('The extracted backup must be outside the destination disk before restoring.');
            }
        }

        // Delete all files in the S3 bucket
        $this->info('Clearing the S3 bucket...');
        foreach ($s3Disk->allDirectories('/') as $directory) {
            $s3Disk->deleteDirectory($directory);
        }

        foreach ($s3Disk->files('/') as $file) {
            $s3Disk->delete($file);
        }

        // Upload extracted files to S3
        $this->info('Uploading extracted files to S3...');
        $this->uploadFilesToS3($extractTo, '/', $s3Disk);
    }

    public function uploadFilesToS3($sourcePath, $destinationPrefix, $s3Disk): void
    {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourcePath));

        foreach ($files as $name => $file) {
            if (! $file->isDir()) {
                $filePath = $file->getRealPath();

                $relativePath = ltrim(substr($filePath, strlen($sourcePath)), '\\/');

                // Remove "storage/app/backup-s3/" if present
                $relativePath = str_replace('storage/app/backup-s3/', '', $relativePath);

                $s3Path = rtrim($destinationPrefix, '/').'/'.$relativePath;

                $this->info("Uploading file: $relativePath");
                $s3Disk->put($s3Path, file_get_contents($filePath));
            }
        }
    }
}
