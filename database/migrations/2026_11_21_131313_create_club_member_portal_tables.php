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
        Schema::create('club_member_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('club_member_id')->unique()->constrained()->cascadeOnDelete();
            $table->uuid('login_id')->unique();
            $table->string('password')->nullable();
            $table->unsignedInteger('session_version')->default(1);
            $table->timestamps();
        });

        Schema::create('club_member_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('club_member_account_id')->constrained()->cascadeOnDelete();
            $table->string('purpose', 20);
            $table->char('digest', 64)->unique();
            $table->timestamp('expires_at');
            $table->text('current_email');
            $table->text('destination');
            $table->unique(['club_member_account_id', 'purpose']);
        });

        Schema::create('club_member_changes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('club_member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('changed_by_member');
            $table->string('field', 32);
            $table->longText('previous_value')->nullable();
            $table->longText('new_value')->nullable();
            $table->timestamps();
            $table->index(['club_member_id', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_member_changes');
        Schema::dropIfExists('club_member_tokens');
        Schema::dropIfExists('club_member_accounts');
    }
};
