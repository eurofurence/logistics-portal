<?php

namespace App\Console\Commands;

use Spatie\Permission\PermissionRegistrar;
use Throwable;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Artisan;

class UpdateRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:roles {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset the roles table and seed the new informations into the database. Please use --force when the app is in production';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $this->info('Updating "roles" table...');
            $this->warn('All manually made changes that was made to the table will be deleted!');
            if ($this->confirm('Do you wish to continue?', true) == true) {
                Artisan::call('down');
                app()[PermissionRegistrar::class]->forgetCachedPermissions();
                $all_ranks = Role::all();
                if ($all_ranks != null or []){
                    $selected = [];
                    foreach ($all_ranks as $rank) {
                        $selected[] = $rank;
                        $this->info('Selected: ' . $rank->name . ' [ID: ' . $rank->id . ']');
                    }
                    $this->newLine(1);

                    if ($selected != null or []){
                        $this->comment('Removing old entries...');
                        $this->newLine(1);
                        foreach ($selected as $item) {
                            Role::where('id', '=', $item->id)->delete();
                            $this->warn('Removing: ' . $item->name . ' [ID: ' . $item->id . ']');
                        }
                        $this->info('Removing complete');
                        $this->newLine(1);

                        $this->info('Adding new entries...');
                        if ($this->option('force') == true) {
                            Artisan::call('db:seed --class=RoleSeeder --force');
                        } else {
                            Artisan::call('db:seed --class=RoleSeeder');
                        }
                        $this->info('Adding completed');
                        $this->newLine(1);
                    } else {
                        $this->info('Adding new entries...');
                        if ($this->option('force') == true) {
                            Artisan::call('db:seed --class=RoleSeeder --force');
                        } else {
                            Artisan::call('db:seed --class=RoleSeeder');
                        }
                        $this->info('Adding completed');
                        $this->newLine(1);
                    }

                } else {
                    $this->info('Nothing to update');
                }

                Artisan::call('up');
                $this->info('Update complete');
                return Command::SUCCESS;
            } else {
                $this->comment('Canceled');
                return Command::SUCCESS;
            }
            return Command::INVALID;
        } catch (Throwable $th) {
            Artisan::call('up');
            return $this->error('Error: ' . $th->getMessage());
            return Command::FAILURE;
        }
    }
}
