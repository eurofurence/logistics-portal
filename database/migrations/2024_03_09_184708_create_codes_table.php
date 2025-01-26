<?php

use App\Models\User;
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
        Schema::create('codes', function (Blueprint $table) {
            $table->id();
            $table->string('label')->unique()->nullable();
            $table->integer('type')->default(0);
            $table->string('note')->nullable();
            $table->foreignIdFor(User::class, 'added_by');
            $table->foreignIdFor(User::class, 'updated_by');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('codes', function (Blueprint $table) {
            $table->dropForeign(['added_by']);
            $table->dropForeign(['updated_by']);
        });

        Schema::dropIfExists('codes');
    }
};
