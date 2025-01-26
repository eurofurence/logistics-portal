<?php

use App\Models\Code;
use App\Models\User;
use App\Models\Storage;
use App\Models\ContainerType;
use App\Models\StorageArea;
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
        Schema::create('storage_containers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->foreignIdFor(StorageArea::class, 'storage_area')->nullable();
            $table->foreignIdFor(ContainerType::class, 'type');
            $table->foreignIdFor(Code::class, 'qr_code')->nullable();
            $table->foreignIdFor(Storage::class, 'home_storage');
            $table->bigInteger('parent_container')->nullable();
            $table->text('comment')->nullable();
            $table->foreignIdFor(User::class, 'added_by');
            $table->softDeletes();
            $table->foreignIdFor(User::class, 'edited_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('storage_containers', function (Blueprint $table) {
            $table->dropForeign(['added_by']);
            $table->dropForeign(['edited_by']);
            $table->dropForeign(['qr_code']);
            $table->dropForeign(['home_storage']);
            $table->dropForeign(['type']);
            $table->dropForeign(['storage_area']);
        });

        Schema::dropIfExists('storage_containers');
    }
};
