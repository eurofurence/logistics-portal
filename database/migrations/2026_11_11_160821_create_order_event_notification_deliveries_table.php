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
        Schema::create('order_event_notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_event_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('type', 20);
            $table->string('cycle_key', 64);
            $table->string('batch_key', 64);
            $table->string('channel', 20);
            $table->boolean('automatic');
            $table->timestamp('deadline_at')->nullable();
            $table->string('status', 20)->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->unique(['order_event_id', 'user_id', 'type', 'batch_key', 'channel'], 'event_notification_delivery_unique');
            $table->index(['status', 'updated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_event_notification_deliveries');
    }
};
