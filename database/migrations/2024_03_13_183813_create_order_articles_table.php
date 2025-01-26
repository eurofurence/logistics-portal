<?php

use App\Models\User;
use App\Models\OrderCategory;
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
        Schema::create('order_articles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->string('picture')->nullable();
            $table->foreignIdFor(User::class, 'added_by');
            $table->foreignIdFor(User::class, 'edited_by');
            $table->foreignIdFor(OrderCategory::class, 'category')->nullable();
            $table->decimal('price_net');
            $table->decimal('price_gross');
            $table->string('currency')->default('EUR');
            $table->text('url')->nullable();
            $table->text('comment')->nullable();
            $table->string('article_number')->nullable();
            $table->decimal('tax_rate')->default(19);
            $table->decimal('returning_deposit')->default(0);
            $table->boolean('locked')->default(false);
            $table->string('locked_reason')->nullable();
            $table->integer('quantity_available')->default(-1);
            $table->json('article_variants')->nullable();
            $table->decimal('packaging_size_per_article')->default(0);
            $table->unsignedBigInteger('packaging_size_per_article_unit')->nullable();
            $table->integer('packaging_article_quantity')->default(0);
            $table->timestamp('deadline')->nullable();
            $table->boolean('auto_calculate')->default(true);
            $table->text('important_note')->nullable();
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
            $table->dropForeign(['category']);
        });

        Schema::dropIfExists('order_articles');
    }
};
