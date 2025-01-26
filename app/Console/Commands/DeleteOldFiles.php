<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DeleteOldFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'files:delete-old';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete old temp files';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->deleteOldExcelTemp();
    }

    public function deleteOldExcelTemp() {
        $disk = 's3';
        $directory = '/export/excel/tmp';

        $files = Storage::disk($disk)->files($directory);

        foreach ($files as $file) {
            $lastModified = Storage::disk($disk)->lastModified($file);
            $lastModified = Carbon::createFromTimestamp($lastModified);

            if ($lastModified->lt(Carbon::now()->subHours(2))) {
                Storage::disk($disk)->delete($file);
                $this->info("Deleted: $file");
            }
        }

        $this->info('Old files deletion completed.');
    }
}
