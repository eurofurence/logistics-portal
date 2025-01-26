<?php

use App\Models\Role;
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
        Schema::create('idp_rank_syncs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->nullable();
            $table->foreignIdFor(Role::class, 'local_role');
            $table->string('idp_group');
            $table->boolean('active')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('idp_rank_syncs', function (Blueprint $table) {
            $table->dropForeign(['local_role']);
        });

        Schema::dropIfExists('idp_rank_syncs');
    }
};
