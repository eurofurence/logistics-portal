<?php

use App\Models\SubUnit;
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
        Schema::create('base_units', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->foreignIdFor(SubUnit::class, 'sub_unit')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->foreignIdFor(User::class, 'added_by');
            $table->foreignIdFor(User::class, 'edited_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('base_units', function (Blueprint $table) {
            $table->dropForeign(['sub_unit']);
            $table->dropForeign(['added_by']);
            $table->dropForeign(['edited_by']);
        });

        Schema::dropIfExists('base_units');
    }
};
