<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Permission::findOrCreate('get-bill-payment-reminder-accountant-notification', 'web');
    }

    public function down(): void
    {
        Permission::where('name', 'get-bill-payment-reminder-accountant-notification')
            ->where('guard_name', 'web')
            ->delete();
    }
};
