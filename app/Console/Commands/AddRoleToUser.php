<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class AddRoleToUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:add_role {user} {role}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a role to a user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user');
        $roleName = $this->argument('role');

        $user = User::find($userId);
        if (!$user) {
            $this->error("User with ID $userId not found.");
            return;
        }

        $role = Role::findByName($roleName);
        if (!$role) {
            $this->error("Role '$roleName' does not exist.");
            return;
        }

        $user->assignRole($roleName);
        $this->info("Role '$roleName' assigned to user '{$user->name}' successfully.");
    }
}
