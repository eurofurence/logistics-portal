<?php

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
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
        Schema::create('department_userrank', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Department::class, 'department_id')->onDelete('cascade');
            $table->foreignIdFor(User::class, 'user_id')->onDelete('cascade');
            $table->foreignIdFor(Role::class, 'role_id')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('department_userrank', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['role_id']);
            $table->dropForeign(['department_id']);
        });

        Schema::dropIfExists('department_userrank');
    }
};
