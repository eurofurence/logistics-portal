<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const FIELDS = ['sona_name', 'first_name', 'last_name', 'street_address', 'postal_code', 'city', 'country', 'email', 'phone', 'comment', 'birth_date', 'joined_at', 'left_at'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('club_members', 'personal_data_encrypted')) {
            Schema::table('club_members', function (Blueprint $table): void {
                $table->boolean('personal_data_encrypted')->default(false);
            });
        }

        Schema::table('club_members', function (Blueprint $table): void {
            foreach (self::FIELDS as $field) {
                $table->text($field)->nullable(in_array($field, ['phone', 'comment', 'left_at'], true))->change();
            }
        });

        DB::table('club_members')->where('personal_data_encrypted', false)->orderBy('id')->chunkById(100, function ($members): void {
            DB::transaction(function () use ($members): void {
                foreach ($members as $member) {
                    $encrypted = ['personal_data_encrypted' => true];
                    foreach (self::FIELDS as $field) {
                        $encrypted[$field] = $member->{$field} === null ? null : Crypt::encryptString($member->{$field});
                    }
                    DB::table('club_members')->where('id', $member->id)->update($encrypted);
                }
            });
        });

        DB::table('media')->where('model_type', 'App\\Models\\ClubMember')->where('collection_name', 'club-member-files')
            ->orderBy('id')->chunkById(100, function ($files): void {
                foreach ($files as $file) {
                    $properties = json_decode($file->custom_properties, true, flags: JSON_THROW_ON_ERROR);
                    if (! isset($properties['encrypted_original_name'])) {
                        $properties['encrypted_original_name'] = Crypt::encryptString($properties['original_name'] ?? $file->file_name);
                    }
                    unset($properties['original_name']);
                    DB::table('media')->where('id', $file->id)->update([
                        'name' => 'Member attachment',
                        'custom_properties' => json_encode($properties, JSON_THROW_ON_ERROR),
                    ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('media')->where('model_type', 'App\\Models\\ClubMember')->where('collection_name', 'club-member-files')
            ->orderBy('id')->chunkById(100, function ($files): void {
                foreach ($files as $file) {
                    $properties = json_decode($file->custom_properties, true, flags: JSON_THROW_ON_ERROR);
                    if (! isset($properties['encrypted_original_name'])) {
                        continue;
                    }
                    $properties['original_name'] = Crypt::decryptString($properties['encrypted_original_name']);
                    unset($properties['encrypted_original_name']);
                    DB::table('media')->where('id', $file->id)->update([
                        'name' => $properties['original_name'],
                        'custom_properties' => json_encode($properties, JSON_THROW_ON_ERROR),
                    ]);
                }
            });

        DB::table('club_members')->where('personal_data_encrypted', true)->orderBy('id')->chunkById(100, function ($members): void {
            DB::transaction(function () use ($members): void {
                foreach ($members as $member) {
                    $decrypted = ['personal_data_encrypted' => false];
                    foreach (self::FIELDS as $field) {
                        $decrypted[$field] = $member->{$field} === null ? null : Crypt::decryptString($member->{$field});
                    }
                    DB::table('club_members')->where('id', $member->id)->update($decrypted);
                }
            });
        });

        Schema::table('club_members', function (Blueprint $table): void {
            foreach (['sona_name', 'first_name', 'last_name', 'street_address', 'postal_code', 'city', 'email'] as $field) {
                $table->string($field)->nullable(false)->change();
            }
            $table->string('country', 2)->nullable(false)->change();
            $table->string('phone', 50)->nullable()->change();
            $table->date('birth_date')->nullable(false)->change();
            $table->date('joined_at')->nullable(false)->change();
            $table->date('left_at')->nullable()->change();
            $table->dropColumn('personal_data_encrypted');
        });
    }
};
