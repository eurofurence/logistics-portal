<?php

use App\Filament\Admin\Pages\ManageTheme;
use App\Filament\App\Resources\ClubMembers\Pages\EditClubMember;
use App\Filament\App\Resources\ClubMembers\Pages\ViewClubMember;
use App\Filament\App\Resources\ClubMembers\RelationManagers\ChangesRelationManager;
use App\Http\Middleware\StartWebSession;
use App\Http\Middleware\VerifyCsrfToken;
use App\Mail\ClubMemberPortalLink;
use App\Models\ClubMember;
use App\Models\ClubMemberAccount;
use App\Models\ClubMemberToken;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Notifications\ClubMemberProfileChanged;
use App\Settings\LoginSettings;
use App\Settings\ThemeSettings;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Livewire\Livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('app'));
    LoginSettings::fake(['whitelist_active' => false]);
    $this->withoutVite();
});

function memberPortalStaff(array $permissions = ['view-any', 'view', 'update']): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission.'-ClubMember', 'web'));
    }

    return $user;
}

function lastMemberPortalLink(string $purpose): string
{
    $mail = Mail::sent(ClubMemberPortalLink::class, fn (ClubMemberPortalLink $mail): bool => $mail->purpose === $purpose)->last();

    return Str::after($mail->url, '#token=');
}

test('member language selection persists across portal pages and validates supported languages', function (): void {
    $previousLocale = app()->getLocale();
    $this->post(route('member-portal.locale.update'), ['locale' => 'de'])->assertOk()->assertJson(['locale' => 'de']);
    $this->get(route('member-portal.login'))->assertInertia(fn (AssertableInertia $page) => $page
        ->where('locale', 'de')->where('ui.access.login.title', 'Anmelden'));
    expect(app()->getLocale())->toBe($previousLocale);
    $this->get(route('member-portal.forgot-password'))->assertInertia(fn (AssertableInertia $page) => $page->where('locale', 'de'));
    $this->postJson(route('member-portal.locale.update'), ['locale' => 'invalid'])->assertUnprocessable()->assertJsonValidationErrors('locale');
    $this->post(route('member-portal.locale.update'), ['locale' => 'en'])->assertOk();
    $this->get(route('member-portal.login'))->assertInertia(fn (AssertableInertia $page) => $page
        ->where('locale', 'en')->where('ui.access.login.title', 'Sign in'));
});

test('the panel and member portal can be used in parallel in the same browser', function (): void {
    app()->instance(VerifyCsrfToken::class, new class(app(), app('encrypter')) extends VerifyCsrfToken
    {
        protected function runningUnitTests(): bool
        {
            return false;
        }
    });

    $staff = memberPortalStaff(['view-any', 'view']);
    $account = ClubMemberAccount::factory()->create();
    $panelCookie = config('session.cookie');
    $memberCookie = $panelCookie.StartWebSession::MEMBER_COOKIE_SUFFIX;
    $panelUrl = route('filament.app.resources.club-members.index');
    $newRequest = function (): void {
        $this->flushSession();
        Auth::forgetGuards();
    };

    $panel = $this->withSession([Auth::guard('web')->getName() => $staff->id])->get($panelUrl)->assertOk();
    $this->withUnencryptedCookie($panelCookie, $panel->getCookie($panelCookie, false)->getValue());

    $newRequest();
    $this->get('/mitglieder/login')->assertRedirect('/members/login');
    $newRequest();
    $this->get('/members/profile')->assertRedirect('/members/login');
    $newRequest();
    $login = $this->get('/members/login')->assertInertia(fn (AssertableInertia $page) => $page->component('ClubMembers/Access')->where('mode', 'login'))
        ->assertCookieMissing($panelCookie)->assertCookieMissing('XSRF-TOKEN');
    $this->withUnencryptedCookie($memberCookie, $login->getCookie($memberCookie, false)->getValue());
    expect($login->getCookie($memberCookie, false)->getPath())->toBe('/members')
        ->and($login->getCookie('MEMBER-XSRF-TOKEN', false)->getPath())->toBe('/members');

    $credentials = ['login_id' => $account->member->sona_name, 'password' => 'member-password-test'];
    $newRequest();
    $this->post('/members/login', $credentials, ['X-XSRF-TOKEN' => $panel->getCookie('XSRF-TOKEN', false)->getValue()])
        ->assertStatus(419);
    $newRequest();
    $memberLogin = $this->post('/members/login', $credentials, ['X-XSRF-TOKEN' => $login->getCookie('MEMBER-XSRF-TOKEN', false)->getValue()])
        ->assertRedirect('/members/profile')->assertCookieMissing($panelCookie);
    $this->withUnencryptedCookie($memberCookie, $memberLogin->getCookie($memberCookie, false)->getValue());

    $newRequest();
    $this->get($panelUrl)->assertOk();
    $newRequest();
    $this->post(route('filament.app.auth.logout'))->assertRedirect()->assertCookieMissing($memberCookie);
    $newRequest();
    $this->get('/members/profile')->assertInertia(fn (AssertableInertia $page) => $page->component('ClubMembers/Profile')->where('loginId', $account->member->sona_name));
    $newRequest();
    $this->get($panelUrl)->assertRedirect();
});

test('staff can invite two distinct accounts with the same address and only the latest link activates a member', function (): void {
    Mail::fake();
    $staff = memberPortalStaff(['view-any', 'view', 'send-portal-invitation']);
    $members = ClubMember::factory()->count(2)->create(['email' => 'shared@example.com']);
    $this->actingAs($staff);

    $invite = fn (ClubMember $member) => Livewire::test(ViewClubMember::class, ['record' => $member->id])
        ->callAction('sendPortalInvitation')->assertNotified();

    $invite($members[0]);
    $obsolete = lastMemberPortalLink(ClubMemberToken::ACTIVATION);
    $invite($members[1]);
    $invite($members[0]);
    $secret = lastMemberPortalLink(ClubMemberToken::ACTIVATION);
    $account = $members[0]->portalAccount()->firstOrFail();

    Mail::assertSentCount(3);
    Mail::assertSent(ClubMemberPortalLink::class, fn (ClubMemberPortalLink $mail): bool => $mail->hasTo('shared@example.com')
        && $mail->loginId === $members[1]->sona_name);
    expect($account->password)->toBeNull()
        ->and($account->tokens()->sole()->digest)->toBe(hash('sha256', $secret));

    $newPassword = ['password' => 'my-new-member-password', 'password_confirmation' => 'my-new-member-password'];
    $this->get(route('member-portal.activation'))->assertInertia(fn (AssertableInertia $page) => $page->component('ClubMembers/Access')->where('mode', 'activation')->missing('auth'));
    $this->post(route('member-portal.activation.redeem'), ['token' => $obsolete, ...$newPassword])->assertSessionHasErrors('token');
    $this->assertGuest(ClubMemberAccount::GUARD);
    $this->post(route('member-portal.activation.redeem'), ['token' => $secret, ...$newPassword])->assertRedirect();
    $this->assertAuthenticatedAs($account, ClubMemberAccount::GUARD);
    $this->assertAuthenticatedAs($staff, 'web');
    expect(Hash::check($newPassword['password'], $account->refresh()->password))->toBeTrue();
    $this->post(route('member-portal.activation.redeem'), ['token' => $secret, ...$newPassword])->assertSessionHasErrors('token');
    $this->post(route('member-portal.logout'))->assertRedirect();
    $this->assertGuest(ClubMemberAccount::GUARD);
    $this->assertAuthenticatedAs($staff, 'web');
});

test('staff can resend login details and reset an activated member account', function (): void {
    Mail::fake();
    $staff = memberPortalStaff(['view-any', 'view', 'send-portal-invitation']);
    $account = ClubMemberAccount::factory()->create();
    $member = $account->member;
    $previousPassword = $account->password;
    $previousVersion = $account->fresh()->session_version;
    $this->actingAs($staff);

    Livewire::test(ViewClubMember::class, ['record' => $member->id])
        ->callAction('sendPortalInvitation')->assertNotified();
    Mail::assertSent(ClubMemberPortalLink::class, fn (ClubMemberPortalLink $mail): bool => $mail->purpose === 'access'
        && $mail->loginId === $member->sona_name && $mail->url === route('member-portal.login') && $mail->hasTo($member->email));
    expect($account->fresh()->password)->toBe($previousPassword)
        ->and($account->fresh()->session_version)->toBe($previousVersion);

    $this->actingAs(memberPortalStaff(['view-any', 'view']));
    Livewire::test(ViewClubMember::class, ['record' => $member->id])
        ->assertActionHidden('resetPortalAccess')
        ->mountAction('resetPortalAccess')->assertActionNotMounted('resetPortalAccess');
    expect($account->fresh()->password)->toBe($previousPassword);
    $this->actingAs($staff);
    Livewire::test(ViewClubMember::class, ['record' => $member->id])
        ->callAction('resetPortalAccess')->assertNotified();
    expect($account->fresh()->password)->toBeNull()
        ->and($account->fresh()->session_version)->toBe($previousVersion + 1);
    $this->post(route('member-portal.authenticate'), ['login_id' => $member->sona_name, 'password' => 'member-password-test'])
        ->assertSessionHasErrors('login_id');
    $this->post(route('member-portal.activation.redeem'), [
        'token' => lastMemberPortalLink(ClubMemberToken::ACTIVATION),
        'password' => 'replacement-member-password',
        'password_confirmation' => 'replacement-member-password',
    ])->assertRedirect();
    expect(Hash::check('replacement-member-password', $account->fresh()->password))->toBeTrue();
});

test('a failed reset email preserves the existing member access', function (): void {
    $staff = memberPortalStaff(['view-any', 'view', 'send-portal-invitation']);
    $account = ClubMemberAccount::factory()->create();
    $password = $account->password;
    $version = $account->fresh()->session_version;
    $this->actingAs($staff);
    Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('Mailer unavailable'));

    Livewire::test(ViewClubMember::class, ['record' => $account->club_member_id])
        ->callAction('resetPortalAccess')->assertNotified();
    expect($account->fresh()->password)->toBe($password)
        ->and($account->fresh()->session_version)->toBe($version);
    $this->assertDatabaseCount('club_member_tokens', 0);
});

test('login follows the current sona name and rejects ambiguous names', function (): void {
    $account = ClubMemberAccount::factory()->create();
    $member = $account->member;
    $oldName = $member->sona_name;
    $member->update(['sona_name' => 'Silver Fox']);

    $this->post(route('member-portal.authenticate'), ['login_id' => $oldName, 'password' => 'member-password-test'])
        ->assertSessionHasErrors('login_id');
    $this->post(route('member-portal.authenticate'), ['login_id' => '  SILVER FOX  ', 'password' => 'member-password-test'])
        ->assertRedirect(route('member-portal.profile'));
    $this->post(route('member-portal.logout'));

    ClubMemberAccount::factory()->for(ClubMember::factory()->create(['sona_name' => 'silver fox']), 'member')->create();
    $this->post(route('member-portal.authenticate'), ['login_id' => 'Silver Fox', 'password' => 'member-password-test'])
        ->assertSessionHasErrors('login_id');

    Mail::fake();
    $this->post(route('member-portal.request-reset'), ['login_id' => 'Silver Fox'])->assertSessionHasNoErrors();
    Mail::assertNothingSent();
});

test('invitation permission and active membership control the Filament action', function (): void {
    Mail::fake();
    $staff = memberPortalStaff(['view-any', 'view']);
    $member = ClubMember::factory()->create();
    $this->actingAs($staff);

    Livewire::test(ViewClubMember::class, ['record' => $member->id])->assertActionHidden('sendPortalInvitation')
        ->mountAction('sendPortalInvitation')->assertActionNotMounted('sendPortalInvitation');
    $staff->givePermissionTo(Permission::findOrCreate('send-portal-invitation-ClubMember', 'web'));

    foreach ([['joined_at' => null], ['left_at' => today()->subDay()->toDateString()], ['joined_at' => today()->addDays(2)->toDateString()]] as $dates) {
        $inactive = ClubMember::factory()->create($dates);
        Livewire::test(ViewClubMember::class, ['record' => $inactive->id])->assertActionHidden('sendPortalInvitation')
            ->mountAction('sendPortalInvitation')->assertActionNotMounted('sendPortalInvitation');
    }

    Mail::assertNothingSent();
    $this->assertDatabaseCount('club_member_accounts', 0);
});

test('a member can edit only their own profile and managers receive one notification over both channels', function (): void {
    Notification::fake();
    $direct = memberPortalStaff();
    $role = Role::findOrCreate('Membership staff', 'web');
    $role->syncPermissions(['view-any-ClubMember', 'view-ClubMember', 'update-ClubMember']);
    $viaRole = User::factory()->create()->assignRole($role)->givePermissionTo('update-ClubMember');
    $master = User::factory()->create()->assignRole(Role::findOrCreate('Master', 'web'));
    $viewer = memberPortalStaff(['view-any', 'view']);
    $locked = memberPortalStaff();
    $locked->update(['locked' => true]);
    $account = ClubMemberAccount::factory()->create();
    $member = $account->member;
    $other = ClubMember::factory()->create(['first_name' => 'Other member']);

    $this->get('/members')->assertRedirect('/members/profile');
    $this->get('/members/profile')->assertRedirect('/members/login');
    $this->post(route('member-portal.authenticate'), ['login_id' => strtoupper($account->member->sona_name), 'password' => 'member-password-test'])
        ->assertRedirect(route('member-portal.profile'));
    $this->assertGuest('web');
    $this->get(route('member-portal.profile'))->assertInertia(fn (AssertableInertia $page) => $page->component('ClubMembers/Profile')
        ->where('profile.first_name', $member->first_name)->missing('profile.comment')->missing('auth'));

    $this->patch(route('member-portal.profile.update'), [
        'id' => $other->id,
        'first_name' => 'Updated first',
        'city' => 'Updated city',
        'left_at' => '2024-01-01',
        'comment' => 'Injected',
        'email' => 'unverified@example.com',
    ])->assertRedirect(route('member-portal.profile'))->assertSessionHasNoErrors();

    expect($member->fresh()->only(['first_name', 'city', 'left_at', 'comment', 'email']))
        ->toBe(['first_name' => 'Updated first', 'city' => 'Updated city', 'left_at' => null, 'comment' => null, 'email' => $member->email])
        ->and($other->fresh()->first_name)->toBe('Other member');
    $change = $member->changes()->where('field', 'first_name')->sole();
    expect($change->previous_value)->toBe($member->first_name)
        ->and($change->new_value)->toBe('Updated first')
        ->and($change->getRawOriginal('new_value'))->not->toBe('Updated first');

    foreach ([$direct, $viaRole, $master] as $recipient) {
        Notification::assertSentTo($recipient, ClubMemberProfileChanged::class, function (ClubMemberProfileChanged $notice, array $channels) use ($member): bool {
            return $notice->memberId === $member->id && $notice->fields === ['first_name', 'city'] && $channels === ['database', 'mail'];
        });
    }

    Notification::assertNotSentTo([$viewer, $locked], ClubMemberProfileChanged::class);
    Notification::assertCount(3);
    $this->patch(route('member-portal.profile.update'), ['first_name' => 'Updated first'])->assertSessionHasNoErrors();
    Notification::assertCount(3);
    expect($member->changes()->count())->toBe(2);
});

test('the confirmed address change creates history and invalidates outstanding reset links', function (): void {
    Mail::fake();
    Notification::fake();
    $staff = memberPortalStaff();
    $account = ClubMemberAccount::factory()->create();
    $member = $account->member;
    $oldEmail = $member->email;
    $this->post(route('member-portal.authenticate'), ['login_id' => $account->member->sona_name, 'password' => 'member-password-test']);
    $this->post(route('member-portal.request-reset'), ['login_id' => $account->member->sona_name])->assertRedirect(route('member-portal.forgot-password'));
    $resetToken = lastMemberPortalLink(ClubMemberToken::RESET);
    Mail::assertSent(ClubMemberPortalLink::class, fn (ClubMemberPortalLink $mail): bool => $mail->purpose === ClubMemberToken::RESET && $mail->hasTo($oldEmail));

    $this->patch(route('member-portal.email.update'), ['email' => 'pending@example.com', 'current_password' => 'wrong'])
        ->assertSessionHasErrors('current_password');
    $this->patch(route('member-portal.email.update'), ['email' => 'pending@example.com', 'current_password' => 'member-password-test'])
        ->assertSessionHasNoErrors();
    $obsolete = lastMemberPortalLink(ClubMemberToken::EMAIL_CHANGE);
    $this->patch(route('member-portal.email.update'), ['email' => 'confirmed@example.com', 'current_password' => 'member-password-test'])
        ->assertSessionHasNoErrors();
    $latest = lastMemberPortalLink(ClubMemberToken::EMAIL_CHANGE);
    Mail::assertSent(ClubMemberPortalLink::class, fn (ClubMemberPortalLink $mail): bool => $mail->purpose === ClubMemberToken::EMAIL_CHANGE && $mail->hasTo('confirmed@example.com'));
    expect($member->fresh()->email)->toBe($oldEmail);
    Notification::assertNothingSent();
    $this->get(route('member-portal.email-change'))->assertSuccessful();
    $this->post(route('member-portal.email-change.redeem'), ['token' => $obsolete])->assertSessionHasErrors('token');
    $this->post(route('member-portal.email-change.redeem'), ['token' => $latest])->assertRedirect(route('member-portal.profile'));
    expect($member->fresh()->email)->toBe('confirmed@example.com')
        ->and($member->changes()->sole()->previous_value)->toBe($oldEmail);
    Notification::assertSentTo($staff, ClubMemberProfileChanged::class);
    $this->post(route('member-portal.reset.redeem'), [
        'token' => $resetToken, 'password' => 'new-reset-password', 'password_confirmation' => 'new-reset-password',
    ])->assertSessionHasErrors('token');
});

test('password changes and resets validate credentials and revoke older member sessions', function (): void {
    Mail::fake();
    Notification::fake();
    memberPortalStaff();
    $account = ClubMemberAccount::factory()->create();
    $this->post(route('member-portal.authenticate'), ['login_id' => $account->member->sona_name, 'password' => 'member-password-test']);
    $oldVersion = $account->session_version ?? $account->fresh()->session_version;
    $this->patch(route('member-portal.password.update'), [
        'current_password' => 'wrong', 'password' => 'new-member-password', 'password_confirmation' => 'new-member-password',
    ])->assertSessionHasErrors('current_password');
    $this->patch(route('member-portal.password.update'), [
        'current_password' => 'member-password-test', 'password' => 'new-member-password', 'password_confirmation' => 'different-password',
    ])->assertSessionHasErrors('password');
    $this->patch(route('member-portal.password.update'), [
        'current_password' => 'member-password-test', 'password' => 'new-member-password', 'password_confirmation' => 'new-member-password',
    ])->assertSessionHasNoErrors();
    Auth::forgetGuards();
    $this->get(route('member-portal.profile'))->assertSuccessful();
    $this->withSession([ClubMemberAccount::SESSION_VERSION => $oldVersion]);
    $this->get(route('member-portal.profile'))->assertRedirect(route('member-portal.login'));

    $this->post(route('member-portal.request-reset'), ['login_id' => $account->member->sona_name]);
    $expired = lastMemberPortalLink(ClubMemberToken::RESET);
    $this->travel(61)->minutes();
    $password = ['password' => 'new-reset-password', 'password_confirmation' => 'new-reset-password'];
    $this->post(route('member-portal.reset.redeem'), ['token' => $expired, ...$password])->assertSessionHasErrors('token');
    $this->post(route('member-portal.request-reset'), ['login_id' => $account->member->sona_name]);
    $secret = lastMemberPortalLink(ClubMemberToken::RESET);
    $this->post(route('member-portal.reset.redeem'), ['token' => $secret, ...$password])->assertRedirect(route('member-portal.login'));
    $this->post(route('member-portal.reset.redeem'), ['token' => $secret, ...$password])->assertSessionHasErrors('token');
    $this->post(route('member-portal.authenticate'), ['login_id' => $account->member->sona_name, 'password' => 'new-member-password'])->assertSessionHasErrors('login_id');
    $this->post(route('member-portal.authenticate'), ['login_id' => $account->member->sona_name, 'password' => $password['password']])->assertRedirect(route('member-portal.profile'));
    Notification::assertNothingSent();
    $this->assertDatabaseCount('club_member_changes', 0);
});

test('a departed member loses their existing session at the start of the departure day', function (): void {
    $this->travelTo(now()->parse('2026-11-22 23:55', 'Europe/Berlin'));
    $member = ClubMember::factory()->create(['left_at' => '2026-11-23']);
    $account = ClubMemberAccount::factory()->for($member, 'member')->create();
    $credentials = ['login_id' => $account->member->sona_name, 'password' => 'member-password-test'];
    $this->post(route('member-portal.authenticate'), $credentials)->assertRedirect(route('member-portal.profile'));

    $this->travel(6)->minutes();
    Auth::forgetGuards();
    $this->get(route('member-portal.profile'))->assertRedirect(route('member-portal.login'));
    $this->post(route('member-portal.authenticate'), $credentials)->assertSessionHasErrors('login_id');
});

test('staff edits appear in the restricted history without triggering a member notification', function (): void {
    Notification::fake();
    $staff = memberPortalStaff(['view-any', 'view', 'update', 'view-history']);
    $viewer = memberPortalStaff(['view-any', 'view']);
    $member = ClubMember::factory()->create();
    $this->actingAs($staff);

    Livewire::test(EditClubMember::class, ['record' => $member->id])
        ->assertSchemaComponentVisible('member-history')
        ->fillForm(['comment' => 'New internal comment'])->call('save')->assertHasNoFormErrors();
    $change = $member->changes()->sole();
    expect($change->field)->toBe('comment')->and($change->staff->is($staff))->toBeTrue();
    Livewire::test(ViewClubMember::class, ['record' => $member->id])->assertSchemaComponentVisible('member-history');
    Livewire::test(ChangesRelationManager::class, ['ownerRecord' => $member, 'pageClass' => ViewClubMember::class])
        ->call('$refresh')->assertCanSeeTableRecords([$change])->assertSee('New internal comment');
    Notification::assertNothingSent();

    $this->actingAs($viewer);
    Livewire::test(ViewClubMember::class, ['record' => $member->id])->assertSchemaComponentHidden('member-history');
    Livewire::test(ChangesRelationManager::class, ['ownerRecord' => $member, 'pageClass' => ViewClubMember::class])->assertForbidden();
});

test('a rolled back change never sends a notification and the panel payload links to the protected record', function (): void {
    $staff = memberPortalStaff();
    $account = ClubMemberAccount::factory()->create();
    $notice = new ClubMemberProfileChanged($account->club_member_id, ['first_name']);
    $staff->notifyNow($notice, ['database']);
    $payload = $staff->notifications()->sole()->data;
    expect($payload['format'])->toBe('filament')
        ->and($payload['actions'][0]['url'])->toBe(route('filament.app.resources.club-members.view', ['record' => $account->club_member_id]))
        ->and($payload['body'])->not->toContain($account->member->first_name);
    $staff->revokePermissionTo('update-ClubMember');
    expect($notice->shouldSend($staff, 'mail'))->toBeFalse();
    $staff->givePermissionTo('update-ClubMember');

    Notification::fake();
    $this->post(route('member-portal.authenticate'), ['login_id' => $account->member->sona_name, 'password' => 'member-password-test']);
    DB::beginTransaction();
    try {
        $this->patch(route('member-portal.profile.update'), ['first_name' => 'Rolled back'])->assertSessionHasNoErrors();
        Notification::assertNothingSent();
    } finally {
        DB::rollBack();
    }

    Notification::assertNothingSent();
    $this->assertDatabaseCount('club_member_changes', 0);
});

test('theming can save and clear a separate member login logo', function (): void {
    config(['filesystems.default' => 'site-assets']);
    Storage::fake('site-assets');
    $disk = Storage::disk('site-assets');
    $disk->put('site_logo/site.png', UploadedFile::fake()->image('site.png')->get());
    $settings = app(ThemeSettings::class);
    $settings->logo = 'site_logo/site.png';
    $settings->save();
    $admin = User::factory()->create()->givePermissionTo(['access-adminpanel', 'access-theme-settings']);
    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(ManageTheme::class)
        ->fillForm(['member_login_logo' => UploadedFile::fake()->image('member.webp')])
        ->call('save')->assertHasNoFormErrors();
    $settings->refresh();
    expect($settings->member_login_logo)->toStartWith('member_login_logo/');
    $this->get(route('branding.member-login-logo'))->assertSuccessful()->assertStreamedContent($disk->get($settings->member_login_logo));

    Livewire::test(ManageTheme::class)->fillForm(['member_login_logo' => null])->call('save')->assertHasNoFormErrors();
    expect($settings->refresh()->member_login_logo)->toBeNull();
    $this->get(route('branding.member-login-logo'))->assertStreamedContent($disk->get('site_logo/site.png'));
    $disk->delete('site_logo/site.png');
    $this->get(route('branding.member-login-logo'))->assertSuccessful();
});
