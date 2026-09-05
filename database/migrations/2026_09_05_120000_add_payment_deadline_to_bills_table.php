<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bills', function (Blueprint $table): void {
            $table->date('payment_deadline')->nullable()->index();
            $table->timestamp('payment_reminder_sent_at')->nullable();
            $table->timestamp('payment_overdue_reminder_sent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table): void {
            $table->dropIndex(['payment_deadline']);
            $table->dropColumn(['payment_deadline', 'payment_reminder_sent_at', 'payment_overdue_reminder_sent_at']);
        });
    }
};
