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
        Schema::create('order_requests', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('message')->nullable();
            $table->text('comment')->nullable();
            $table->text('url')->nullable();
            $table->smallInteger('status')->default(0);
            $table->boolean('status_notifications')->default(true);
            $table->softDeletes();
            $table->integer('quantity')->default(0);
            $table->foreignIdFor(OrderEvent::class, 'order_event_id');
            $table->foreignIdFor(Department::class, 'department_id');
            $table->foreignIdFor(User::class, 'added_by');
            $table->foreignIdFor(User::class, 'edited_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['added_by']);
            $table->dropForeign(['edited_by']);
            $table->dropForeign(['department_id']);
            $table->dropForeign(['order_event_id']);
        });

        Schema::dropIfExists('order_requests');
    }
};
