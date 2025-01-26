<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DeleteS3Files extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 's3:delete-files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete specific files from S3 storage';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // List of files to be deleted
        $filesToDelete = [
            //'01J35X45R103XHCA2VF9V2A0N1.png',
        ];

        // Loop through all files and delete them directly from S3
        foreach ($filesToDelete as $fileName) {
            if (Storage::disk('s3')->exists($fileName)) {
                Storage::disk('s3')->delete($fileName);
                $this->info("Deleted: $fileName");
            } else {
                $this->warn("File not found on S3: $fileName");
            }
        }

        $this->info('File deletion process completed.');
        return Command::SUCCESS;
    }
}
