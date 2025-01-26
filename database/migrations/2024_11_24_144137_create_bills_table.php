<?php

use App\Models\User;
use App\Models\Department;
use App\Models\OrderEvent;
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
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->index('title');
            $table->string('title');
            $table->text('description');
            $table->decimal('value', 10, 2)->default(0);
            $table->index('status');
            $table->string('status')->default('open');
            $table->text('comment')->nullable();
            $table->string('currency')->default('EUR');
            $table->decimal('advance_payment_value', 10, 2)->nullable();
            $table->string('advance_payment_receiver')->nullable();
            $table->foreignIdFor(Department::class, 'department_id');
            $table->foreignIdFor(OrderEvent::class, 'order_event_id');
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
        Schema::table('order_articles', function (Blueprint $table) {
            $table->dropForeign(['added_by']);
            $table->dropForeign(['edited_by']);
            $table->dropForeign(['department_id']);
            $table->dropForeign(['order_event_id']);
        });

        Schema::dropIfExists('bills');
    }
};
