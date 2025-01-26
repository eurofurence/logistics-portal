<?php

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
        Schema::create('pdf_downloads', function (Blueprint $table) {
            $table->id();
            $table->string('view');
            $table->json('data1')->nullable();
            $table->json('data2')->nullable();
            $table->json('data3')->nullable();
            $table->json('data4')->nullable();
            $table->json('data5')->nullable();
            $table->json('file_settings')->nullable();
            $table->dateTime('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdf_downloads');
    }
};
