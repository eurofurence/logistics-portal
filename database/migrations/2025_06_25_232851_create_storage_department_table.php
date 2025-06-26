<?php

use App\Models\Storage;
use App\Models\Department;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('storage_department', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Department::class, 'department');
            $table->foreignIdFor(Storage::class, 'storage');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('storage_department');
    }
};
