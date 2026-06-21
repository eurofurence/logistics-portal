<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Permission::create(['name' => 'download-Bill-zip', 'guard_name' => 'web']);
        Permission::create(['name' => 'download-all-bills-zip', 'guard_name' => 'web']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::where('name', 'download-Bill-zip')->delete();
        Permission::where('name', 'download-all-bills-zip')->delete();
    }
};
