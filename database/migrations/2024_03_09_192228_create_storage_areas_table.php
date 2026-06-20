<?php

use App\Models\Code;
use App\Models\Storage;
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
        Schema::create('storage_areas', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->foreignIdFor(Storage::class, 'storage');
            $table->text('comment')->nullable();
            $table->foreignIdFor(User::class, 'added_by');
            $table->foreignIdFor(User::class, 'edited_by');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('storage_areas', function (Blueprint $table) {
            $table->dropForeign(['added_by']);
            $table->dropForeign(['edited_by']);
            $table->dropForeign(['qr_code']);
            $table->dropForeign(['storage']);
        });

        Schema::dropIfExists('storage_areas');
    }
};
