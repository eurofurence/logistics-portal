<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('club_members', function (Blueprint $table): void {
            foreach (['first_name', 'last_name', 'street_address', 'postal_code', 'city', 'country', 'birth_date', 'joined_at'] as $field) {
                $table->text($field)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('club_members', function (Blueprint $table): void {
            foreach (['first_name', 'last_name', 'street_address', 'postal_code', 'city', 'country', 'birth_date', 'joined_at'] as $field) {
                $table->text($field)->nullable(false)->change();
            }
        });
    }
};
