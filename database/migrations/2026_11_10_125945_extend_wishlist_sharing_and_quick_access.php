<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (['wishlist_user' => ['wishlist_id', 'user_id'], 'wishlist_items' => ['wishlist_id', 'order_article_id']] as $tableName => $columns) {
            DB::table($tableName)->select($columns)->selectRaw('MIN(id) AS kept_id')->groupBy($columns)
                ->havingRaw('COUNT(*) > 1')->get()->each(function (object $duplicate) use ($tableName, $columns): void {
                    DB::table($tableName)->where($columns[0], $duplicate->{$columns[0]})
                        ->where($columns[1], $duplicate->{$columns[1]})->where('id', '!=', $duplicate->kept_id)->delete();
                });
        }

        Schema::table('wishlist_user', function (Blueprint $table): void {
            $table->string('access', 4)->default('read');
            $table->unique(['wishlist_id', 'user_id']);
        });
        Schema::table('wishlist_items', function (Blueprint $table): void {
            $table->unique(['wishlist_id', 'order_article_id']);
        });
        Schema::create('wishlist_department', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('wishlist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('access', 4)->default('read');
            $table->timestamps();
            $table->unique(['wishlist_id', 'department_id']);
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('last_wishlist_id')->nullable()->constrained('wishlists')->nullOnDelete();
        });
        Permission::findOrCreate('manage-all-Wishlist', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('last_wishlist_id');
        });
        Schema::dropIfExists('wishlist_department');
        Schema::table('wishlist_user', function (Blueprint $table): void {
            $table->dropUnique(['wishlist_id', 'user_id']);
            $table->dropColumn('access');
        });
        Schema::table('wishlist_items', function (Blueprint $table): void {
            $table->dropUnique(['wishlist_id', 'order_article_id']);
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
