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
        Schema::create('club_members', function (Blueprint $table) {
            $table->id();
            foreach (['sona_name', 'first_name', 'last_name', 'street_address', 'postal_code', 'city', 'email'] as $field) {
                $table->string($field);
            }
            $table->string('country', 2);
            $table->string('phone', 50)->nullable();
            $table->date('birth_date');
            $table->date('joined_at');
            $table->date('left_at')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_members');
    }
};
