<?php

namespace App\Console\Commands;

use App\Models\Whitelist;
use Illuminate\Console\Command;

class AddToWhitelist extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whitelist:add {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds a new email to the whitelist';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get the email
        $email = $this->argument('email');

        // Adds the email
        try {
            $whitelist = new Whitelist();
            $whitelist->email = $email;
            $whitelist->save();

            $this->info("Email {$email} has been added to the whitelist.");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::INVALID;
    }
}
