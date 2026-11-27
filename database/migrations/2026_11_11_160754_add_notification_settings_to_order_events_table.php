<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order_events', function (Blueprint $table) {
            $table->boolean('deadline_reminder_enabled')->default(false);
            $table->string('deadline_reminder_mode')->default('relative');
            $table->unsignedSmallInteger('deadline_reminder_days')->default(7);
            $table->timestamp('deadline_reminder_at')->nullable();
            $table->json('deadline_reminder_channels')->nullable();
            $table->boolean('opening_notification_enabled')->default(false);
            $table->string('opening_notification_mode')->default('manual');
            $table->timestamp('opening_notification_at')->nullable();
            $table->json('opening_notification_channels')->nullable();
        });
        DB::table('order_events')->update([
            'deadline_reminder_enabled' => false,
            'deadline_reminder_channels' => json_encode(['mail', 'database']),
            'opening_notification_channels' => json_encode(['mail', 'database']),
        ]);
        Schema::table('order_events', function (Blueprint $table) {
            $table->boolean('deadline_reminder_enabled')->default(true)->change();
        });
        Permission::findOrCreate('send-test-email-OrderEvent', 'web');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_events', function (Blueprint $table) {
            $table->dropColumn(['deadline_reminder_enabled', 'deadline_reminder_mode', 'deadline_reminder_days', 'deadline_reminder_at', 'deadline_reminder_channels', 'opening_notification_enabled', 'opening_notification_mode', 'opening_notification_at', 'opening_notification_channels']);
        });
    }
};
