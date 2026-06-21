<?php

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_sub_category', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->foreignIdFor(Department::class, 'department');
            $table->foreignIdFor(User::class, 'added_by');
            $table->foreignIdFor(User::class, 'edited_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_sub_category', function (Blueprint $table) {
            $table->dropForeign(['added_by']);
            $table->dropForeign(['edited_by']);
            $table->dropForeign(['department']);
        });

        Schema::dropIfExists('inventory_sub_category');
    }
};
