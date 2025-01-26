<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DeleteSpatieBackups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:delete-s3-backups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes spatie backups from s3';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Deleting from s3...');

        $s3Files = Storage::disk('s3')->allFiles('/' . config('app.name'));

        if ($s3Files) {
            if ($this->confirm('Are you sure?', true)) {
                foreach ($s3Files as $file) {
                    Storage::disk('s3')->delete($file);
                    $this->info("Deleted: $file");
                }
            }
        } else {
            $this->info('No spatie backups found on S3');
        }

        $this->info('Finished');
    }
}
