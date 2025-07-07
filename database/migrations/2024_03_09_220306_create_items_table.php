<?php

use App\Models\Code;
use App\Models\User;
use App\Models\BaseUnit;
use App\Models\Department;
use App\Models\Storage;
use App\Models\StorageContainer;
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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('shortname')->unique()->nullable();
            $table->string('serialnumber')->nullable();
            $table->unsignedBigInteger('weight_g')->unsigned()->nullable();
            $table->boolean('stackable')->nullable();
            $table->foreignIdFor(BaseUnit::class, 'unit')->nullable();
            $table->timestamp('due_date')->nullable();
            $table->timestamp('sorted_out')->nullable();
            $table->text('description')->nullable();
            $table->text('comment')->nullable();
            $table->foreignIdFor(Department::class, 'department');
            $table->foreignIdFor(User::class, 'added_by');
            $table->foreignIdFor(User::class, 'edited_by');
            $table->unsignedBigInteger('price')->unsigned()->nullable();
            $table->boolean('locked')->default(0);
            $table->foreignIdFor(User::class, 'specific_editor')->nullable();
            $table->timestamp('buy_date')->nullable();
            $table->foreignIdFor(Code::class, 'qr_code')->nullable();
            $table->foreignIdFor(StorageContainer::class, 'storage_container_id')->nullable();
            $table->boolean('dangerous_good')->default(false);
            $table->boolean('big_size')->default(false);
            $table->boolean('needs_truck')->default(false);
            $table->text('url')->nullable();
            $table->foreignIdFor(Storage::class, 'storage')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropForeign(['added_by']);
            $table->dropForeign(['edited_by']);
            $table->dropForeign(['department']);
            $table->dropForeign(['unit']);
            $table->dropForeign(['specific_editor']);
            $table->dropForeign(['qr_code']);
            $table->dropForeign(['storage_container_id']);
            $table->dropForeign(['storage']);
        });

        Schema::dropIfExists('items');
    }
};
