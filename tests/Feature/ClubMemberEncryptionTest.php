<?php

use App\Models\ClubMember;
use App\Models\Permission;
use App\Models\User;
use App\Services\ClubMemberFiles;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

test('encrypts every personal member field while preserving dates and nullable values', function () {
    $data = ClubMember::factory()->raw(['phone' => '+49 123456', 'comment' => 'Private note', 'left_at' => '2026-01-01']);

    $member = ClubMember::factory()->create($data)->fresh();

    foreach ([...ClubMember::TEXT_FIELDS, ...ClubMember::DATE_FIELDS] as $field) {
        $stored = DB::table('club_members')->where('id', $member->id)->value($field);
        expect($stored)->not->toBe($data[$field]);
        expect(Crypt::decryptString($stored))->toBe($data[$field]);
    }
    expect($member->birth_date->toDateString())->toBe('1990-01-01');
    expect($member->joined_at->toDateString())->toBe('2020-01-01');
    expect($member->left_at->toDateString())->toBe('2026-01-01');
    expect($member->email)->toBe($data['email']);

    $member->update(['phone' => null, 'comment' => null, 'left_at' => null]);

    $this->assertDatabaseHas('club_members', ['id' => $member->id, 'phone' => null, 'comment' => null, 'left_at' => null]);
    expect($member->fresh()->left_at)->toBeNull();
});

test('refuses plaintext or damaged ciphertext instead of returning it as member data', function (string $field) {
    $member = ClubMember::factory()->create();
    DB::table('club_members')->where('id', $member->id)->update([$field => 'unprotected value']);

    expect(fn () => $member->fresh()->{$field})->toThrow(DecryptException::class);
})->with(['email', 'birth_date']);

test('keeps original member attachment names encrypted in database metadata', function () {
    $user = User::factory()->create();
    foreach (['view', 'send-email', 'manage-files'] as $ability) {
        $user->givePermissionTo(Permission::findOrCreate($ability.'-ClubMember', 'web'));
    }
    $this->actingAs($user);
    config([
        'filesystems.default' => 'member-encryption',
        'filesystems.disks.member-encryption' => ['driver' => 'local', 'root' => storage_path('framework/testing/disks/member-encryption')],
    ]);
    Storage::fake('member-encryption');
    $member = ClubMember::factory()->create();

    $file = app(ClubMemberFiles::class)->prepareEmail($member, [UploadedFile::fake()->image('Ada Example.png')], [])->first();

    expect(ClubMemberFiles::originalName($file))->toBe('Ada Example.png');
    $stored = DB::table('media')->where('id', $file->id)->first();
    expect($stored->name)->toBe('Member attachment');
    expect($stored->custom_properties)->not->toContain('Ada Example');
    expect($stored->file_name)->not->toContain('Ada Example');
});

test('migrates existing and deleted member data and filenames without double encryption and can roll back', function () {
    $originalConnection = DB::getDefaultConnection();
    config(['database.connections.member-encryption-test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
    DB::setDefaultConnection('member-encryption-test');

    try {
        $originalMigration = require database_path('migrations/2026_10_06_082720_create_club_members_table.php');
        $originalMigration->up();
        Schema::create('media', function (Blueprint $table): void {
            $table->id();
            $table->string('model_type');
            $table->string('collection_name');
            $table->string('name');
            $table->string('file_name');
            $table->text('custom_properties');
        });
        $data = ClubMember::factory()->raw(['comment' => 'Legacy private note']);
        DB::table('club_members')->insert([...$data, 'id' => 1]);
        DB::table('club_members')->insert([...$data, 'id' => 2, 'deleted_at' => '2026-01-01 00:00:00']);
        DB::table('media')->insert([
            'id' => 1, 'model_type' => ClubMember::class, 'collection_name' => ClubMember::FILE_COLLECTION,
            'name' => 'Ada Example.png', 'file_name' => 'random.png',
            'custom_properties' => json_encode(['original_name' => 'Ada Example.png', 'other' => 'preserved']),
        ]);
        $migration = require database_path('migrations/2026_10_06_114403_encrypt_club_member_personal_data.php');

        $migration->up();
        $ciphertext = DB::table('club_members')->where('id', 1)->value('email');
        $migration->up();

        expect(DB::table('club_members')->where('id', 1)->value('email'))->toBe($ciphertext);
        foreach ([1, 2] as $id) {
            foreach ([...ClubMember::TEXT_FIELDS, ...ClubMember::DATE_FIELDS] as $field) {
                $stored = DB::table('club_members')->where('id', $id)->value($field);
                expect($stored === null ? null : Crypt::decryptString($stored))->toBe($data[$field]);
            }
        }
        expect(DB::table('media')->value('custom_properties'))->not->toContain('Ada Example');

        $migration->down();

        $this->assertDatabaseHas('club_members', ['id' => 1, ...$data]);
        $this->assertDatabaseHas('club_members', ['id' => 2, ...$data, 'deleted_at' => '2026-01-01 00:00:00']);
        expect(json_decode(DB::table('media')->value('custom_properties'), true))->toBe(['other' => 'preserved', 'original_name' => 'Ada Example.png']);
    } finally {
        DB::setDefaultConnection($originalConnection);
        DB::purge('member-encryption-test');
    }
});
