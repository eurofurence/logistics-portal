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
        Schema::create('department_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id')->unsigned()->oncascade();
            $table->unsignedBigInteger('user_id')->unsigned();

            // ForeignKey for department_id
            $table->foreign('department_id')
            ->references('id')
            ->on('departments')
            ->onDelete('cascade'); // Deletes entries in rank_user when the associated rank is deleted

            // ForeignKey for user_id
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade'); // Deletes entries in rank_user when the associated user is deleted
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('department_user', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['user_id']);
        });

        Schema::dropIfExists('department_members');
    }
};
