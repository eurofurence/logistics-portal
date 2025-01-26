<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DeleteFilamentExports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exports:delete-filament-exports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes filament exports from s3';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Deleting from s3...');

        $s3Files = Storage::disk('s3')->allFiles('/filament_exports');

        if ($s3Files) {
            if ($this->confirm('Are you sure?', true)) {
                foreach ($s3Files as $file) {
                    Storage::disk('s3')->delete($file);
                    $this->info("Deleted: $file");
                }
            }
        } else {
            $this->info('No filament exports found on S3');
        }

        $this->info('Finished');
    }
}
