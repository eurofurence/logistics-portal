<?php

use App\Models\Item;
use App\Models\User;
use App\Models\Department;
use App\Models\OrderEvent;
use App\Models\OrderArticle;
use App\Models\OrderRequest;
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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('delivery_provider')->nullable();
            $table->string('delivery_by')->nullable();
            $table->text('delivery_destination')->nullable();
            $table->string('tracking_number')->nullable();
            $table->timestamp('delivery_date')->nullable();
            $table->decimal('delivery_costs')->default(0);
            $table->boolean('instant_delivery')->default(false);
            $table->foreignIdFor(Department::class, 'department_id');
            $table->foreignIdFor(User::class, 'added_by');
            $table->foreignIdFor(User::class, 'edited_by');
            $table->integer('amount')->default(1);
            $table->decimal('price_net')->default(0);
            $table->decimal('price_gross')->default(0);
            $table->decimal('tax_rate', total: 8, places: 2)->default(19);
            $table->string('payment_method')->nullable();
            $table->string('currency')->default('EUR');
            $table->text('url')->nullable();
            $table->text('contact')->nullable();
            $table->json('tags')->nullable();
            $table->boolean('dangerous_good')->default(false);
            $table->boolean('big_size')->default(false);
            $table->boolean('needs_truck')->default(false);
            $table->timestamp('ordered_at')->nullable();
            $table->boolean('booked_to_inventory')->default(false);
            $table->foreignIdFor(Item::class, 'inv_id')->nullable();
            $table->foreignIdFor(OrderEvent::class, 'order_event_id');
            $table->text('comment')->nullable();
            $table->string('status')->default('open');
            $table->text('picture')->nullable();
            $table->foreignIdFor(OrderArticle::class, 'order_article_id')->nullable();
            $table->text('article_number')->nullable();
            $table->text('user_note')->nullable();
            $table->foreignIdFor(OrderRequest::class, 'order_request_id')->nullable();
            $table->boolean('special_delivery')->default(0);
            $table->string('special_flag_text')->nullable();
            $table->decimal('returning_deposit')->default(0);
            $table->decimal('discount_net')->nullable();
            $table->softDeletes();
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
            $table->dropForeign(['inv_id']);
            $table->dropForeign(['order_event_id']);
            $table->dropForeign(['order_article_id']);
            $table->dropForeign(['order_request_id']);
        });

        Schema::dropIfExists('orders');
    }
};
