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
            $table->unsignedBigInteger('department_id')->unsigned();
            $table->unsignedBigInteger('user_id')->unsigned();
            $table->unsignedBigInteger('role_id')->unsigned()->nullable();

            // ForeignKey for department_id
            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->onDelete('cascade'); // Deletes entries in department_user when the associated department is deleted

            // ForeignKey for user_id
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade'); // Deletes entries in department_user when the associated user is deleted

            // ForeignKey for role_id
            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->onDelete('cascade'); // Deletes entries in department_user when the associated department is deleted
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
