<?php

use App\Filament\App\Resources\ClubMembers\ClubMemberResource;
use App\Filament\App\Resources\ClubMembers\Pages\CreateClubMember;
use App\Filament\App\Resources\ClubMembers\Pages\EditClubMember;
use App\Filament\App\Resources\ClubMembers\Pages\ListClubMembers;
use App\Filament\App\Resources\ClubMembers\Pages\ViewClubMember;
use App\Mail\ClubMemberMessage;
use App\Models\ClubMember;
use App\Models\Permission;
use App\Models\User;
use App\Services\ClubMemberFiles;
use App\Settings\GeneralSettings;
use App\Settings\LoginSettings;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('app'));
    app(GeneralSettings::class)->timezone = 'Europe/Berlin';
    app(LoginSettings::class)->whitelist_active = false;
    config([
        'filesystems.default' => 'member-storage',
        'filesystems.disks.member-storage' => ['driver' => 'local', 'root' => storage_path('framework/testing/disks/member-storage')],
    ]);
    Storage::fake('member-storage');
});

function clubMemberUser(array $abilities = ['view-any', 'view', 'create', 'update', 'delete', 'restore', 'send-email', 'manage-files']): User
{
    $user = User::factory()->create();
    foreach ($abilities as $ability) {
        $user->givePermissionTo(Permission::findOrCreate($ability.'-ClubMember', 'web'));
    }

    return $user;
}

function clubMemberFormData(array $overrides = []): array
{
    return array_replace([
        'sona_name' => 'Silver Fox', 'first_name' => 'Ada', 'last_name' => 'Example',
        'street_address' => 'Teststraße 12', 'postal_code' => '12345', 'city' => 'Berlin',
        'country' => 'DE', 'email' => 'ada@example.com', 'birth_date' => '1990-01-01',
        'joined_at' => '2020-01-01', 'left_at' => null, 'phone' => null, 'comment' => null,
    ], $overrides);
}

test('creates members with optional fields empty and allows shared names and addresses', function () {
    $this->actingAs(clubMemberUser());
    ClubMember::factory()->create(clubMemberFormData());

    Livewire::test(CreateClubMember::class)->fillForm(clubMemberFormData())->call('create')->assertHasNoFormErrors()->assertRedirect();

    expect(ClubMember::where('email', 'ada@example.com')->count())->toBe(2);
});

test('validates required member fields', function (string $field) {
    $this->actingAs(clubMemberUser());

    Livewire::test(CreateClubMember::class)->fillForm(clubMemberFormData([$field => null]))->call('create')->assertHasFormErrors([$field => 'required']);

    expect(ClubMember::count())->toBe(0);
})->with(['sona_name', 'first_name', 'last_name', 'street_address', 'postal_code', 'city', 'country', 'email', 'birth_date', 'joined_at']);

test('rejects invalid member values', function (array $data, string $field) {
    $this->actingAs(clubMemberUser());
    $this->travelTo(now()->setDate(2026, 10, 6));

    Livewire::test(CreateClubMember::class)->fillForm(clubMemberFormData($data))->call('create')->assertHasFormErrors([$field]);

    expect(ClubMember::count())->toBe(0);
})->with([
    'email' => [['email' => 'invalid'], 'email'],
    'future birthday' => [['birth_date' => '2027-01-01'], 'birth_date'],
    'entry before birth' => [['joined_at' => '1989-01-01'], 'joined_at'],
    'departure before entry' => [['left_at' => '2019-12-31'], 'left_at'],
    'long name' => [['sona_name' => str_repeat('a', 256)], 'sona_name'],
    'long phone' => [['phone' => str_repeat('1', 51)], 'phone'],
    'long comment' => [['comment' => str_repeat('a', 10001)], 'comment'],
]);

test('updates member data and renders escaped comments on the detail page', function () {
    $this->actingAs(clubMemberUser());
    $member = ClubMember::factory()->create();

    Livewire::test(EditClubMember::class, ['record' => $member->id])
        ->fillForm(['comment' => '<script>alert(1)</script>', 'phone' => '+49 123456'])->call('save')->assertHasNoFormErrors();
    expect($member->fresh()->phone)->toBe('+49 123456');
    Livewire::test(ViewClubMember::class, ['record' => $member->id])->assertSee('&lt;script&gt;', escape: false)->assertDontSee('<script>alert(1)</script>', escape: false);
});

test('matches status filters to membership boundaries', function (string $joined, ?string $left, string $expected) {
    $this->actingAs(clubMemberUser());
    $this->travelTo(now()->setDate(2026, 10, 6)->setTime(12, 0));
    $member = ClubMember::factory()->create(['joined_at' => $joined, 'left_at' => $left]);

    expect($member->membershipStatus())->toBe($expected);
    Livewire::test(ListClubMembers::class)->filterTable('status', $expected)->assertCanSeeTableRecords([$member]);
    $other = $expected === 'active' ? 'departed' : 'active';
    Livewire::test(ListClubMembers::class)->filterTable('status', $other)->assertCanNotSeeTableRecords([$member]);
})->with([
    'future entry' => ['2026-10-07', null, 'planned'],
    'entry today' => ['2026-10-06', null, 'active'],
    'departure tomorrow' => ['2020-01-01', '2026-10-07', 'active'],
    'departure today' => ['2020-01-01', '2026-10-06', 'departed'],
]);

test('filters text fields and dates independently', function () {
    $this->actingAs(clubMemberUser());
    $member = ClubMember::factory()->create(clubMemberFormData(['comment' => 'Board contact']));
    $other = ClubMember::factory()->create(['comment' => 'Other', 'joined_at' => '2021-01-01', 'country' => 'AT']);

    Livewire::test(ListClubMembers::class)->filterTable('comment', ['value' => 'Board'])->assertCanSeeTableRecords([$member])->assertCanNotSeeTableRecords([$other]);
    Livewire::test(ListClubMembers::class)->filterTable('joined_at', ['from' => '2020-01-01', 'until' => '2020-01-01'])->assertCanSeeTableRecords([$member])->assertCanNotSeeTableRecords([$other]);
    Livewire::test(ListClubMembers::class)->filterTable('country', 'AT')->assertCanSeeTableRecords([$other])->assertCanNotSeeTableRecords([$member]);
});

test('denies resource access without member permissions and excludes global search', function () {
    $this->actingAs(clubMemberUser([]));
    $member = ClubMember::factory()->create(['sona_name' => 'Private Fox']);
    $this->get(ClubMemberResource::getUrl('index'))->assertForbidden();
    $this->getJson(ClubMemberResource::getUrl('view', ['record' => $member]))->assertNotFound();
    expect(ClubMemberResource::getGlobalSearchResults('Private Fox'))->toBeEmpty();
});

test('enforces separate permissions', function (string $ability, string $permission) {
    $user = clubMemberUser(['view-any', 'view']);
    $this->actingAs($user);
    $member = ClubMember::factory()->create();

    expect(Gate::allows($ability, $member))->toBeFalse();
    $user->givePermissionTo(Permission::findOrCreate($permission.'-ClubMember', 'web'));
    expect(Gate::allows($ability, $member))->toBeTrue();
})->with([
    ['update', 'update'], ['delete', 'delete'], ['sendEmail', 'send-email'], ['manageFiles', 'manage-files'],
]);

test('stores email uploads on the configured disk and downloads from their original disk after a switch', function () {
    $this->actingAs(clubMemberUser());
    $member = ClubMember::factory()->create();
    $files = app(ClubMemberFiles::class)->prepareEmail($member, [UploadedFile::fake()->image('member.png')], []);
    $file = $files->first();
    expect($file->disk)->toBe('member-storage');
    Storage::disk('member-storage')->assertExists($file->getPathRelativeToRoot());
    expect(Storage::disk('member-storage')->getVisibility($file->getPathRelativeToRoot()))->toBe('private');
    config(['filesystems.default' => 'another-storage']);
    Storage::fake('another-storage');

    $this->get(route('club-members.files.download', ['member' => $member->id, 'media' => $file->id]))->assertOk()->assertDownload('member.png');
    $mail = new ClubMemberMessage('Subject', 'Body', $files);
    expect($mail->hasAttachment(Attachment::fromStorageDisk('member-storage', $file->getPathRelativeToRoot())->as('member.png')->withMime('image/png')))->toBeTrue();
});

test('blocks foreign attachments and downloads', function () {
    $this->actingAs(clubMemberUser());
    $member = ClubMember::factory()->create();
    $other = ClubMember::factory()->create();
    $file = app(ClubMemberFiles::class)->prepareEmail($other, [UploadedFile::fake()->image('private.png')], [])->first();

    $this->getJson(route('club-members.files.download', ['member' => $member->id, 'media' => $file->id]))->assertNotFound();
    expect(fn () => app(ClubMemberFiles::class)->prepareEmail($member, [], [$file->id]))->toThrow(ValidationException::class);
    expect($member->media()->count())->toBe(0);
});

test('requires authentication and view permission to download files', function () {
    $owner = clubMemberUser();
    $this->actingAs($owner);
    $member = ClubMember::factory()->create();
    $file = app(ClubMemberFiles::class)->prepareEmail($member, [UploadedFile::fake()->image('private.png')], [])->first();
    $this->actingAs(clubMemberUser([]));
    $this->get(route('club-members.files.download', ['member' => $member->id, 'media' => $file->id]))->assertForbidden();
});

test('permits email uploads with file permission without member update permission', function () {
    Mail::fake();
    $this->actingAs(clubMemberUser(['view-any', 'view', 'send-email', 'manage-files']));
    $member = ClubMember::factory()->create();

    Livewire::test(ViewClubMember::class, ['record' => $member->id])
        ->callAction('sendEmail', ['recipient' => 'other@example.com', 'subject' => 'Welcome', 'body' => 'Hello', 'uploads' => [UploadedFile::fake()->image('welcome.png')]])
        ->assertHasNoActionErrors();
    $file = $member->media()->first();
    expect($file)->not->toBeNull();
    Mail::assertSent(ClubMemberMessage::class, fn (ClubMemberMessage $mail): bool => $mail->hasTo('other@example.com') && $mail->hasSubject('Welcome') && $mail->files->count() === 1);
});

test('sends existing attachments without file permission', function () {
    $this->actingAs(clubMemberUser());
    $member = ClubMember::factory()->create();
    $file = app(ClubMemberFiles::class)->prepareEmail($member, [UploadedFile::fake()->image('existing.png')], [])->first();
    $this->actingAs(clubMemberUser(['view-any', 'view', 'send-email']));
    Mail::fake();

    Livewire::test(ViewClubMember::class, ['record' => $member->id])->callAction('sendEmail', [
        'recipient' => $member->email, 'subject' => 'Documents', 'body' => 'Attached', 'attachments' => [$file->id],
    ])->assertHasNoActionErrors();
    Mail::assertSent(ClubMemberMessage::class, fn (ClubMemberMessage $mail): bool => $mail->files->first()->id === $file->id);
});

test('rejects uploads without file permission before storage or mail', function () {
    $this->actingAs(clubMemberUser(['view-any', 'view', 'send-email']));
    $member = ClubMember::factory()->create();
    Mail::fake();

    expect(fn () => app(ClubMemberFiles::class)->prepareEmail($member, [UploadedFile::fake()->image('forbidden.png')], []))
        ->toThrow(AuthorizationException::class);
    expect($member->media()->count())->toBe(0);
    Mail::assertNothingSent();
});

test('rejects invalid files and oversized uploads', function (string $name, int $size, string $mime) {
    $this->actingAs(clubMemberUser());
    $member = ClubMember::factory()->create();
    expect(fn () => app(ClubMemberFiles::class)->prepareEmail($member, [UploadedFile::fake()->create($name, $size, $mime)], []))->toThrow(ValidationException::class);
    expect($member->media()->count())->toBe(0);
})->with([
    'executable' => ['script.php', 1, 'text/x-php'],
    'oversized PDF' => ['large.pdf', 10001, 'application/pdf'],
]);

test('enforces combined email size and member file count before adding files', function () {
    $this->actingAs(clubMemberUser());
    $member = ClubMember::factory()->create();
    $service = app(ClubMemberFiles::class);
    $service->prepareEmail($member, array_map(fn (int $i): UploadedFile => UploadedFile::fake()->image('image'.$i.'.png'), range(1, 5)), []);
    expect(fn () => $service->prepareEmail($member, [UploadedFile::fake()->image('sixth.png')], []))->toThrow(ValidationException::class);
    expect($member->media()->count())->toBe(5);
    $other = ClubMember::factory()->create();
    expect(fn () => $service->prepareEmail($other, [
        UploadedFile::fake()->create('a.pdf', 8000, 'application/pdf'),
        UploadedFile::fake()->create('b.pdf', 8000, 'application/pdf'),
        UploadedFile::fake()->create('c.pdf', 8000, 'application/pdf'),
    ], []))->toThrow(ValidationException::class);
    expect($other->media()->count())->toBe(0);
});

test('preserves files through soft deletion and restoration and blocks sending while deleted', function () {
    $this->actingAs(clubMemberUser());
    $member = ClubMember::factory()->create();
    $file = app(ClubMemberFiles::class)->prepareEmail($member, [UploadedFile::fake()->image('keep.png')], [])->first();
    $member->delete();
    expect(Gate::allows('sendEmail', $member))->toBeFalse();
    expect(Gate::allows('manageFiles', $member))->toBeFalse();
    expect(Gate::allows('restore', $member))->toBeTrue();
    $member->restore();
    expect($member->media()->count())->toBe(1);
    Storage::disk('member-storage')->assertExists($file->getPathRelativeToRoot());
});

test('saves files from the edit tab and keeps them when saving unchanged data', function () {
    $this->actingAs(clubMemberUser());
    $member = ClubMember::factory()->create();

    Livewire::test(EditClubMember::class, ['record' => $member->id])
        ->fillForm(['files' => [UploadedFile::fake()->image('tab.png')]])->call('save')->assertHasNoFormErrors();
    expect($member->media()->count())->toBe(1);
    Livewire::test(EditClubMember::class, ['record' => $member->id])->fillForm(['comment' => 'Updated'])->call('save')->assertHasNoFormErrors();
    expect($member->media()->count())->toBe(1);
    expect($member->fresh()->comment)->toBe('Updated');
});

test('saves member data without changing files when file permission is absent', function () {
    $this->actingAs(clubMemberUser());
    $member = ClubMember::factory()->create();
    $file = app(ClubMemberFiles::class)->prepareEmail($member, [UploadedFile::fake()->image('keep.png')], [])->first();
    $this->actingAs(clubMemberUser(['view-any', 'view', 'update']));

    Livewire::test(EditClubMember::class, ['record' => $member->id])->fillForm(['comment' => 'No file changes'])->call('save')->assertHasNoFormErrors();
    expect($member->fresh()->comment)->toBe('No file changes');
    expect($member->media()->count())->toBe(1);
    Storage::disk('member-storage')->assertExists($file->getPathRelativeToRoot());
});

test('replaces a file at the five file limit', function () {
    $this->actingAs(clubMemberUser());
    $member = ClubMember::factory()->create();
    $service = app(ClubMemberFiles::class);
    $files = $service->prepareEmail($member, array_map(fn (int $i): UploadedFile => UploadedFile::fake()->image('image'.$i.'.png'), range(1, 5)), []);

    $service->sync($member, [...$files->skip(1)->pluck('uuid')->all(), UploadedFile::fake()->image('replacement.png')]);

    expect($member->media()->count())->toBe(5);
    expect($member->media()->whereKey($files->first()->id)->exists())->toBeFalse();
    Storage::disk('member-storage')->assertMissing($files->first()->getPathRelativeToRoot());
});

test('keeps new files after mail failure and retries without duplicate uploads', function () {
    $this->actingAs(clubMemberUser());
    $member = ClubMember::factory()->create();
    Mail::shouldReceive('to')->once()->with('retry@example.com')->andReturnSelf();
    Mail::shouldReceive('send')->once()->andThrow(new RuntimeException('Mailer unavailable'));
    Mail::shouldReceive('getDefaultDriver')->andReturn('array');

    $page = Livewire::test(ViewClubMember::class, ['record' => $member->id])
        ->callAction('sendEmail', ['recipient' => 'retry@example.com', 'subject' => 'Retry', 'body' => 'Hello', 'uploads' => [UploadedFile::fake()->image('retry.png')]])
        ->assertNotified(Notification::make()->danger()->title(__('members.mail_failed'))->body(__('members.mail_failed_hint')));
    expect($member->media()->count())->toBe(1);

    Mail::fake();
    $page->callMountedAction()->assertHasNoActionErrors();
    Mail::assertSentCount(1);
    expect($member->media()->count())->toBe(1);
});

test('denies sending after permission is revoked on an open dialog', function () {
    Mail::fake();
    $user = clubMemberUser();
    $this->actingAs($user);
    $member = ClubMember::factory()->create();
    $page = Livewire::test(ViewClubMember::class, ['record' => $member->id])->mountAction('sendEmail');
    $user->revokePermissionTo('send-email-ClubMember');

    $page->callMountedAction();
    Mail::assertNothingSent();
    expect($member->media()->count())->toBe(0);
});

test('denies saving an already opened member after update permission is revoked', function () {
    $user = clubMemberUser();
    $this->actingAs($user);
    $member = ClubMember::factory()->create(['comment' => 'Original']);
    $page = Livewire::test(EditClubMember::class, ['record' => $member->id])->fillForm(['comment' => 'Forbidden']);
    $user->revokePermissionTo('update-ClubMember');

    $page->call('save')->assertForbidden();
    expect($member->fresh()->comment)->toBe('Original');
});

test('rejects crafted uploads in the mail dialog without file permission', function () {
    Mail::fake();
    $this->actingAs(clubMemberUser(['view-any', 'view', 'send-email']));
    $member = ClubMember::factory()->create();

    Livewire::test(ViewClubMember::class, ['record' => $member->id])
        ->mountAction('sendEmail')->fillForm(['recipient' => $member->email, 'subject' => 'Test', 'body' => 'Body'], 'mountedActionSchema0')
        ->set('mountedActions.0.data.uploads', ['forged-file'])->callMountedAction()->assertForbidden();

    Mail::assertNothingSent();
    expect($member->media()->count())->toBe(0);
});

test('does not store uploads when a mail dialog is cancelled', function () {
    Mail::fake();
    $this->actingAs(clubMemberUser());
    $member = ClubMember::factory()->create();

    Livewire::test(ViewClubMember::class, ['record' => $member->id])->mountAction('sendEmail')
        ->fillForm(['uploads' => [UploadedFile::fake()->image('cancel.png')]], 'mountedActionSchema0')->unmountAction();
    expect($member->media()->count())->toBe(0);
    Mail::assertNothingSent();
});

test('rejects missing attachments before sending', function () {
    Mail::fake();
    $this->actingAs(clubMemberUser());
    $member = ClubMember::factory()->create();
    $file = app(ClubMemberFiles::class)->prepareEmail($member, [UploadedFile::fake()->image('missing.png')], [])->first();
    Storage::disk($file->disk)->delete($file->getPathRelativeToRoot());

    Livewire::test(ViewClubMember::class, ['record' => $member->id])->callAction('sendEmail', [
        'recipient' => $member->email, 'subject' => 'Missing', 'body' => 'Body', 'attachments' => [$file->id],
    ])->assertHasActionErrors(['attachments']);
    Mail::assertNothingSent();
});

test('validates mail recipient reply address subject and body', function (string $field, mixed $value) {
    Mail::fake();
    $this->actingAs(clubMemberUser());
    $member = ClubMember::factory()->create();

    Livewire::test(ViewClubMember::class, ['record' => $member->id])->callAction('sendEmail', array_replace([
        'recipient' => $member->email, 'subject' => 'Subject', 'body' => 'Message',
    ], [$field => $value]))->assertHasActionErrors([$field]);
    Mail::assertNothingSent();
})->with([
    ['recipient', 'invalid'], ['reply_email', 'invalid'], ['reply_email', null], ['reply_email', str_repeat('a', 250).'@example.com'], ['subject', null], ['body', null],
]);

test('renders escaped email text', function () {
    $mail = new ClubMemberMessage('Subject', '<script>alert(1)</script>', collect());
    $mail->assertSeeInHtml('&lt;script&gt;', escape: false);
    $mail->assertDontSeeInHtml('<script>alert(1)</script>', escape: false);
});

test('filters every member text field', function (string $field) {
    $this->actingAs(clubMemberUser());
    $value = $field === 'email' ? 'filter@example.com' : 'UniqueMatch';
    $member = ClubMember::factory()->create([$field => $value]);
    $other = ClubMember::factory()->create([$field => $field === 'email' ? 'other@example.com' : 'Other']);
    Livewire::test(ListClubMembers::class)->filterTable($field, ['value' => 'UniqueMatch'])->assertCanNotSeeTableRecords([$other]);
    Livewire::test(ListClubMembers::class)->filterTable($field, ['value' => $value])->assertCanSeeTableRecords([$member])->assertCanNotSeeTableRecords([$other]);
})->with(['sona_name', 'first_name', 'last_name', 'street_address', 'postal_code', 'city', 'email', 'phone', 'comment']);

test('filters every date field with inclusive boundaries', function (string $field) {
    $this->actingAs(clubMemberUser());
    $member = ClubMember::factory()->create([$field => '2020-01-01']);
    $other = ClubMember::factory()->create([$field => '2020-01-02']);

    Livewire::test(ListClubMembers::class)->filterTable($field, ['from' => '2020-01-01', 'until' => '2020-01-01'])
        ->assertCanSeeTableRecords([$member])->assertCanNotSeeTableRecords([$other]);
})->with(['birth_date', 'joined_at', 'left_at']);

test('filters empty phone and departure values and the recycle bin', function () {
    $this->actingAs(clubMemberUser());
    $empty = ClubMember::factory()->create(['phone' => null, 'left_at' => null]);
    $filled = ClubMember::factory()->create(['phone' => '+49 123', 'left_at' => '2025-01-01']);
    $deleted = ClubMember::factory()->create(['deleted_at' => now()]);

    Livewire::test(ListClubMembers::class)->filterTable('phone_present', false)->assertCanSeeTableRecords([$empty])->assertCanNotSeeTableRecords([$filled, $deleted]);
    Livewire::test(ListClubMembers::class)->filterTable('departure_present', true)->assertCanSeeTableRecords([$filled])->assertCanNotSeeTableRecords([$empty]);
    Livewire::test(ListClubMembers::class)->filterTable('trashed', false)->assertCanSeeTableRecords([$deleted])->assertCanNotSeeTableRecords([$filled, $empty]);
});

test('sends mixed existing and new attachments from a table action', function () {
    $this->actingAs(clubMemberUser());
    $member = ClubMember::factory()->create();
    $file = app(ClubMemberFiles::class)->prepareEmail($member, [UploadedFile::fake()->image('existing.png')], [])->first();
    Mail::fake();

    Livewire::test(ListClubMembers::class)->callAction(TestAction::make('sendEmail')->table($member), [
        'recipient' => $member->email, 'subject' => 'Mixed', 'body' => 'Files',
        'attachments' => [$file->id], 'uploads' => [UploadedFile::fake()->image('new.png')],
    ])->assertHasNoActionErrors();

    Mail::assertSent(ClubMemberMessage::class, fn (ClubMemberMessage $mail): bool => $mail->hasTo($member->email) && $mail->files->count() === 2);
    expect($member->media()->count())->toBe(2);
});

test('does not send or retain new media when private file storage fails', function () {
    $this->actingAs(clubMemberUser());
    $member = ClubMember::factory()->create();
    Mail::fake();
    Storage::disk('member-storage')->put('unrelated.txt', 'keep');
    Storage::partialMock()->shouldReceive('disk')->with('member-storage')->andThrow(new RuntimeException('Storage unavailable'));

    Livewire::test(ViewClubMember::class, ['record' => $member->id])->callAction('sendEmail', [
        'recipient' => $member->email, 'subject' => 'Storage', 'body' => 'Files',
        'uploads' => [UploadedFile::fake()->image('failure.png')],
    ])->assertNotified(Notification::make()->danger()->title(__('members.storage_failed')));

    Mail::assertNothingSent();
    expect($member->media()->count())->toBe(0);
});

test('allows PDF and Office document uploads', function (string $extension, string $mime) {
    $this->actingAs(clubMemberUser());
    $member = ClubMember::factory()->create();

    $files = app(ClubMemberFiles::class)->prepareEmail($member, [UploadedFile::fake()->create('document.'.$extension, 10, $mime)], []);

    expect($files)->toHaveCount(1);
    expect($files->first()->getCustomProperty('original_name'))->toBe('document.'.$extension);
})->with([
    ['pdf', 'application/pdf'],
    ['docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
    ['xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
]);

test('defaults reply address to the logged in user and sends it as reply to', function () {
    Mail::fake();
    $user = clubMemberUser();
    $this->actingAs($user);
    $member = ClubMember::factory()->create();

    Livewire::test(ViewClubMember::class, ['record' => $member->id])
        ->mountAction('sendEmail')
        ->assertSchemaStateSet(['reply_email' => $user->email, 'priority' => 3], 'mountedActionSchema0')
        ->fillForm(['subject' => 'Welcome', 'body' => 'Hello'], 'mountedActionSchema0')
        ->callMountedAction()->assertHasNoActionErrors();

    Mail::assertSent(ClubMemberMessage::class, fn (ClubMemberMessage $mail): bool => $mail->hasTo($member->email) && $mail->hasReplyTo($user->email));
});

test('uses the edited reply address instead of the logged in user email', function () {
    Mail::fake();
    $user = clubMemberUser();
    $this->actingAs($user);
    $member = ClubMember::factory()->create();

    Livewire::test(ViewClubMember::class, ['record' => $member->id])->callAction('sendEmail', [
        'subject' => 'Welcome', 'body' => 'Hello', 'reply_email' => 'office@example.com',
    ])->assertHasNoActionErrors();

    Mail::assertSent(ClubMemberMessage::class, fn (ClubMemberMessage $mail): bool => $mail->hasReplyTo('office@example.com') && ! $mail->hasReplyTo($user->email));
});

test('sends the selected priority from the grouped table action', function (int $priority) {
    Mail::fake();
    $this->actingAs(clubMemberUser());
    $member = ClubMember::factory()->create();

    Livewire::test(ListClubMembers::class)->callAction(TestAction::make('sendEmail')->table($member), [
        'subject' => 'Priority', 'body' => 'Message', 'priority' => $priority,
    ])->assertHasNoActionErrors();

    Mail::assertSent(ClubMemberMessage::class, fn (ClubMemberMessage $mail): bool => $mail->messagePriority === $priority);
})->with(['high' => 1, 'normal' => 3, 'low' => 5]);

test('sets the selected priority on the outgoing email headers', function (int $priority) {
    $message = new ClubMemberMessage('Priority', 'Message', collect(), 'reply@example.com', $priority);
    $sent = Mail::mailer('array')->to('recipient@example.com')->send($message);

    expect($sent->getSymfonySentMessage()->getOriginalMessage()->getPriority())->toBe($priority);
})->with(['high' => 1, 'normal' => 3, 'low' => 5]);

test('rejects missing or unsupported mail priority', function (mixed $priority) {
    Mail::fake();
    $this->actingAs(clubMemberUser());
    $member = ClubMember::factory()->create();

    Livewire::test(ViewClubMember::class, ['record' => $member->id])->callAction('sendEmail', [
        'subject' => 'Priority', 'body' => 'Message', 'priority' => $priority,
    ])->assertHasActionErrors(['priority']);
    Mail::assertNothingSent();
})->with(['missing' => null, 'unsupported' => 2, 'invalid' => 'urgent']);

test('enforces ten megabytes for existing and new attachments combined', function (int $newFileKilobytes, bool $allowed) {
    $this->actingAs(clubMemberUser());
    $member = ClubMember::factory()->create();
    $service = app(ClubMemberFiles::class);
    $file = $service->prepareEmail($member, [UploadedFile::fake()->image('existing.png')], [])->first();
    $file->update(['size' => 5 * 1024 * 1024]);
    $upload = UploadedFile::fake()->create('new.pdf', $newFileKilobytes, 'application/pdf');

    if ($allowed) {
        expect($service->prepareEmail($member, [$upload], [$file->id]))->toHaveCount(2);
        expect($member->media()->count())->toBe(2);
    } else {
        expect(fn () => $service->prepareEmail($member, [$upload], [$file->id]))->toThrow(ValidationException::class);
        expect($member->media()->count())->toBe(1);
    }
})->with([
    'exactly ten megabytes' => [5120, true],
    'over ten megabytes' => [5121, false],
]);

test('rejects more than ten megabytes of existing attachments in the dialog without sending', function () {
    Mail::fake();
    $this->actingAs(clubMemberUser());
    $member = ClubMember::factory()->create();
    $service = app(ClubMemberFiles::class);
    $files = $service->prepareEmail($member, [UploadedFile::fake()->image('first.png'), UploadedFile::fake()->image('second.png')], []);
    foreach ($files as $file) {
        $file->update(['size' => 6 * 1024 * 1024]);
    }

    Livewire::test(ViewClubMember::class, ['record' => $member->id])->callAction('sendEmail', [
        'subject' => 'Too large', 'body' => 'Documents', 'attachments' => $files->pluck('id')->all(),
    ])->assertHasActionErrors(['attachments']);

    Mail::assertNothingSent();
    expect($member->media()->count())->toBe(2);
});
