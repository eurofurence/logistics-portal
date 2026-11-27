<?php

use App\Filament\Admin\Resources\Departments\DepartmentResource;
use App\Filament\Admin\Resources\Departments\Pages\ListDepartments;
use App\Models\Department;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Settings\LoginSettings;
use Filament\Events\ServingFilament;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Event;
use Laravel\Octane\Events\RequestTerminated;
use Livewire\Livewire;
use LivewireUI\Spotlight\Commands\Logout;
use LivewireUI\Spotlight\Spotlight;

function spotlightDepartmentLead(): User
{
    Role::factory()->create(['id' => 1, 'name' => 'Master', 'guard_name' => 'web']);
    $actor = User::factory()->create();
    test()->actingAs($actor);
    $department = Department::factory()->create();
    $lead = Role::factory()->create();
    $lead->givePermissionTo(Permission::findOrCreate('view-department-members', 'web'));
    $actor->departmentMemberships()->create(['department_id' => $department->id, 'role_id' => $lead->id]);

    return $actor;
}

afterEach(function () {
    Spotlight::$commands = [];
});

test('rebuilds spotlight for app admin and app again without retaining foreign panel commands', function () {
    $actor = spotlightDepartmentLead();
    $this->actingAs($actor);
    $appPageId = md5(route('filament.app.pages.manage-department-members'));
    $adminPageId = md5(route('filament.admin.pages.dashboard'));

    foreach (['app', 'admin', 'app'] as $panelId) {
        $panel = Filament::getPanel($panelId);
        Filament::setCurrentPanel($panel);
        $panel->boot();
        ServingFilament::dispatch();

        expect(array_key_exists($appPageId, Spotlight::$commands))->toBe($panelId === 'app');
        expect(array_key_exists($adminPageId, Spotlight::$commands))->toBe($panelId === 'admin');
    }

    $commandIds = array_keys(Spotlight::$commands);
    ServingFilament::dispatch();
    expect(array_keys(Spotlight::$commands))->toBe($commandIds);

    Livewire::test(Spotlight::class)
        ->call('execute', $appPageId)
        ->assertRedirect(route('filament.app.pages.manage-department-members'));
});

test('removes previously authorized page commands when the user changes or logs out', function () {
    config()->set('livewire-ui-spotlight.commands', [Logout::class]);
    $this->actingAs(spotlightDepartmentLead());
    Filament::setCurrentPanel(Filament::getPanel('app'));
    ServingFilament::dispatch();
    $pageId = md5(route('filament.app.pages.manage-department-members'));
    expect(Spotlight::$commands)->toHaveKey($pageId);

    $this->actingAs(User::factory()->create());
    ServingFilament::dispatch();
    expect(Spotlight::$commands)->not->toHaveKey($pageId);

    Filament::auth()->logout();
    ServingFilament::dispatch();
    expect(Spotlight::$commands)->toHaveCount(1);
    expect(array_values(Spotlight::$commands)[0])->toBeInstanceOf(Logout::class);
});

test('clears spotlight commands when an octane request terminates', function () {
    $this->actingAs(spotlightDepartmentLead());
    Filament::setCurrentPanel(Filament::getPanel('app'));
    ServingFilament::dispatch();
    expect(Spotlight::$commands)->not->toBeEmpty();

    Event::dispatch(new RequestTerminated(app(), app(), request(), response('')));

    expect(Spotlight::$commands)->toBeEmpty();
});

test('serves the admin panel and retains resource and user menu commands through livewire', function () {
    $actor = spotlightDepartmentLead();
    $actor->assignRole('Master');
    $settings = app(LoginSettings::class);
    $settings->whitelist_active = false;
    $settings->two_factor_enabled = false;
    $settings->save();
    $this->actingAs($actor);

    $this->get(route('filament.admin.pages.dashboard'))->assertSuccessful();

    $resourceCommandId = md5(DepartmentResource::class.ListDepartments::class);
    expect(Spotlight::$commands)->toHaveKey($resourceCommandId);
    expect(Spotlight::$commands)->toHaveKey(md5(Filament::getLogoutUrl()));
    Livewire::test(Spotlight::class)
        ->call('execute', $resourceCommandId)
        ->assertRedirect(route('filament.admin.resources.departments.index'));

});

test('serves the app panel with its authorized spotlight pages', function () {
    $actor = spotlightDepartmentLead();
    $settings = app(LoginSettings::class);
    $settings->whitelist_active = false;
    $settings->two_factor_enabled = false;
    $settings->save();
    $this->actingAs($actor);

    $this->get(route('filament.app.pages.dashboard'))->assertSuccessful();

    expect(Spotlight::$commands)->toHaveKey(md5(route('filament.app.pages.manage-department-members')));
});
