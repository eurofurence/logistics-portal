<?php

use App\Filament\Admin\Resources\Departments\Pages\EditDepartment;
use App\Filament\Admin\Resources\Departments\RelationManagers\DepartmentMembersRelationManager;
use App\Filament\Admin\Resources\Roles\Pages\CreateRole;
use App\Filament\Admin\Resources\Roles\Pages\EditRole;
use App\Filament\Admin\Resources\Roles\Pages\ListRoles;
use App\Filament\App\Pages\ManageDepartmentMembers;
use App\Models\Department;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\DepartmentMemberManager;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** @return array{actor: User, department: Department, lead: Role, member: User, rank: Role} */
function departmentManagementScenario(): array
{
    if (! Role::query()->whereKey(1)->exists()) {
        Role::factory()->create(['id' => 1, 'name' => 'Master', 'guard_name' => 'web']);
    }
    $actor = User::factory()->create();
    test()->actingAs($actor);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    $department = Department::factory()->create();
    $lead = Role::factory()->create(['name' => 'Department lead']);
    foreach (['view-department-members', 'add-department-members', 'update-department-member-roles', 'remove-department-members'] as $permission) {
        $lead->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    $actor->departmentMemberships()->create(['department_id' => $department->id, 'role_id' => $lead->id]);
    $member = User::factory()->create();
    $rank = Role::factory()->create(['name' => 'Member rank', 'assignable_in_app' => true]);
    $member->departmentMemberships()->create(['department_id' => $department->id, 'role_id' => $rank->id]);

    return compact('actor', 'department', 'lead', 'member', 'rank');
}

test('shows one row per member with all department ranks and locked badge', function () {
    ['department' => $department, 'member' => $member] = departmentManagementScenario();
    $second = Role::factory()->create(['name' => 'Second rank']);
    $member->departmentMemberships()->create(['department_id' => $department->id, 'role_id' => $second->id]);
    $member->update(['locked' => true]);
    $outsider = User::factory()->create();

    Livewire::test(ManageDepartmentMembers::class)
        ->assertCanSeeTableRecords([$member])->assertCanNotSeeTableRecords([$outsider])
        ->assertSee('Member rank')->assertSee('Second rank')->assertSee(__('department_members.locked'))
        ->searchTable($member->name)->assertCountTableRecords(1)
        ->filterTable('locked', '0')->assertCanNotSeeTableRecords([$member]);
});

test('department management links still target app when generated in the admin panel', function () {
    departmentManagementScenario();
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    expect(ManageDepartmentMembers::getUrl())->toBe(route('filament.app.pages.manage-department-members'));
    expect(ManageDepartmentMembers::canAccess())->toBeFalse();
    Livewire::test(ManageDepartmentMembers::class)->assertForbidden();
});

test('guests are redirected to the app panel login', function () {
    Filament::setCurrentPanel(Filament::getPanel('app'));

    $this->get(ManageDepartmentMembers::getUrl(panel: 'app'))
        ->assertRedirect(Filament::getPanel('app')->getLoginUrl());
});

test('global permissions alone do not open the app management page', function () {
    ['actor' => $actor, 'department' => $department, 'lead' => $lead] = departmentManagementScenario();
    $lead->revokePermissionTo('view-department-members');
    $actor->givePermissionTo('view-department-members');

    Livewire::test(ManageDepartmentMembers::class)->assertForbidden();
    expect(app(DepartmentMemberManager::class)->can($actor, $department->id, 'view'))->toBeFalse();
});

test('global department managers can manage all departments without department membership', function (bool $master) {
    ['actor' => $actor, 'department' => $department, 'member' => $member, 'rank' => $rank] = departmentManagementScenario();
    $actor->departmentMemberships()->delete();
    $globalRole = $master ? Role::findByName('Master', 'web') : Role::factory()->create();
    if (! $master) {
        $globalRole->givePermissionTo(Permission::findOrCreate('manage-all-department-members', 'web'));
    }
    $actor->assignRole($globalRole);
    $other = Department::factory()->create();
    $newMember = User::factory()->create();
    $manager = app(DepartmentMemberManager::class);

    expect(ManageDepartmentMembers::canAccess())->toBeTrue();
    expect($manager->departments($actor)->pluck('id')->all())->toEqualCanonicalizing([$department->id, $other->id]);
    Livewire::test(ManageDepartmentMembers::class)->set('departmentId', $department->id)
        ->assertCanSeeTableRecords([$member]);
    foreach (['view', 'add', 'update', 'remove'] as $operation) {
        expect($manager->can($actor, $other->id, $operation))->toBeTrue();
        expect($manager->can($actor, 0, $operation))->toBeFalse();
    }

    $manager->save($actor, $other->id, $newMember->id, [], true);
    $this->assertDatabaseHas('department_user', ['department_id' => $other->id, 'user_id' => $newMember->id, 'role_id' => null]);
    $manager->save($actor, $other->id, $newMember->id, [$rank->id]);
    $this->assertDatabaseHas('department_user', ['department_id' => $other->id, 'user_id' => $newMember->id, 'role_id' => $rank->id]);
    $manager->remove($actor, $other->id, $newMember->id);
    $this->assertDatabaseMissing('department_user', ['department_id' => $other->id, 'user_id' => $newMember->id]);
})->with(['master' => true, 'global rank' => false]);

test('a department rank with the global management permission does not grant global access', function () {
    ['actor' => $actor, 'department' => $department, 'lead' => $lead] = departmentManagementScenario();
    $lead->givePermissionTo(Permission::findOrCreate('manage-all-department-members', 'web'));
    $other = Department::factory()->create();
    $manager = app(DepartmentMemberManager::class);

    expect($manager->departments($actor)->pluck('id')->all())->toBe([$department->id]);
    foreach (['view', 'add', 'update', 'remove'] as $operation) {
        expect($manager->can($actor, $other->id, $operation))->toBeFalse();
    }
});

test('revoking global management removes access to departments outside existing department rights', function () {
    ['actor' => $actor, 'department' => $department] = departmentManagementScenario();
    $globalRole = Role::factory()->create();
    $globalRole->givePermissionTo(Permission::findOrCreate('manage-all-department-members', 'web'));
    $actor->assignRole($globalRole);
    $other = Department::factory()->create();
    $manager = app(DepartmentMemberManager::class);
    expect($manager->can($actor, $other->id, 'update'))->toBeTrue();

    $globalRole->revokePermissionTo('manage-all-department-members');

    expect($manager->can($actor, $other->id, 'update'))->toBeFalse();
    expect($manager->departments($actor)->pluck('id')->all())->toBe([$department->id]);
    expect($manager->can($actor, $department->id, 'update'))->toBeTrue();
});

test('global management preserves protected ranks and does not grant admin access', function () {
    ['actor' => $actor, 'department' => $department, 'member' => $member] = departmentManagementScenario();
    $globalRole = Role::factory()->create();
    $globalRole->givePermissionTo(Permission::findOrCreate('manage-all-department-members', 'web'));
    $actor->assignRole($globalRole);
    $protected = Role::factory()->create(['assignable_in_app' => false]);
    $manager = app(DepartmentMemberManager::class);
    $protectedMembership = $member->departmentMemberships()->create(['department_id' => $department->id, 'role_id' => $protected->id]);

    expect($actor->canAccessPanel(Filament::getPanel('admin')))->toBeFalse();
    expect($manager->can($actor, $department->id, 'update', true))->toBeFalse();
    $manager->save($actor, $department->id, $member->id, []);
    $this->assertModelExists($protectedMembership);
    expect(fn () => $manager->save($actor, $department->id, $member->id, [$protected->id]))->toThrow(ValidationException::class);
    expect(fn () => $manager->save($actor, $department->id, $member->id, [1]))->toThrow(ValidationException::class);
    $member->departmentMemberships()->create(['department_id' => $department->id, 'role_id' => 1]);
    expect(fn () => $manager->remove($actor, $department->id, $member->id))->toThrow(ValidationException::class);
    $this->assertDatabaseHas('department_user', ['department_id' => $department->id, 'user_id' => $member->id, 'role_id' => 1]);
});

test('department permissions do not authorize actions in another department', function () {
    ['actor' => $actor, 'member' => $member] = departmentManagementScenario();
    $other = Department::factory()->create();
    $member->departmentMemberships()->create(['department_id' => $other->id, 'role_id' => null]);

    Livewire::test(ManageDepartmentMembers::class)->set('departmentId', $other->id)
        ->assertCanNotSeeTableRecords([$member])->assertCountTableRecords(0);
    expect(fn () => app(DepartmentMemberManager::class)->save($actor, $other->id, $member->id, []))
        ->toThrow(HttpException::class);
    $this->assertDatabaseHas('department_user', ['department_id' => $other->id, 'user_id' => $member->id]);
});

test('each app mutation requires its own department permission', function (string $permission, string $operation) {
    ['actor' => $actor, 'department' => $department, 'lead' => $lead] = departmentManagementScenario();
    $lead->revokePermissionTo($permission);
    $actor->givePermissionTo($permission);

    expect(app(DepartmentMemberManager::class)->can($actor, $department->id, $operation))->toBeFalse();
})->with([
    ['add-department-members', 'add'],
    ['update-department-member-roles', 'update'],
    ['remove-department-members', 'remove'],
]);

test('adds an existing user without ranks and prevents duplicate membership', function () {
    ['actor' => $actor, 'department' => $department] = departmentManagementScenario();
    $newMember = User::factory()->create();
    Livewire::test(ManageDepartmentMembers::class)
        ->callAction(TestAction::make('addMember')->table(), ['user_id' => $newMember->id, 'roles' => []])
        ->assertHasNoActionErrors()->assertNotified();
    $this->assertDatabaseHas('department_user', ['department_id' => $department->id, 'user_id' => $newMember->id, 'role_id' => null]);

    expect(fn () => app(DepartmentMemberManager::class)->save($actor, $department->id, $newMember->id, [], true))->toThrow(ValidationException::class);
    expect($newMember->departmentMemberships()->count())->toBe(1);
});

test('changes only assignable ranks while preserving protected global and other department ranks', function () {
    ['actor' => $actor, 'department' => $department, 'member' => $member, 'rank' => $rank] = departmentManagementScenario();
    $protected = Role::factory()->create(['name' => 'Protected']);
    $replacement = Role::factory()->create(['assignable_in_app' => true]);
    $protectedMembership = $member->departmentMemberships()->create(['department_id' => $department->id, 'role_id' => $protected->id]);
    $other = Department::factory()->create();
    $otherMembership = $member->departmentMemberships()->create(['department_id' => $other->id, 'role_id' => $rank->id]);
    $member->assignRole($protected);

    Livewire::test(ManageDepartmentMembers::class)
        ->callAction(TestAction::make('editRanks')->table($member), ['roles' => [$replacement->id]])
        ->assertHasNoActionErrors()->assertNotified();

    $this->assertModelExists($protectedMembership);
    $this->assertModelExists($otherMembership);
    $this->assertDatabaseMissing('department_user', ['department_id' => $department->id, 'user_id' => $member->id, 'role_id' => $rank->id]);
    $this->assertDatabaseHas('department_user', ['department_id' => $department->id, 'user_id' => $member->id, 'role_id' => $replacement->id]);
    expect($member->fresh()->hasRole($protected))->toBeTrue();
});

test('unchanged saves preserve membership identities and empty rank selection keeps membership', function () {
    ['actor' => $actor, 'department' => $department, 'member' => $member, 'rank' => $rank] = departmentManagementScenario();
    $membership = $member->departmentMemberships()->sole();
    $manager = app(DepartmentMemberManager::class);
    $manager->save($actor, $department->id, $member->id, [$rank->id]);
    $this->assertModelExists($membership);
    $manager->save($actor, $department->id, $member->id, []);
    $emptyMembership = $member->departmentMemberships()->sole();
    expect($emptyMembership->role_id)->toBeNull();
    $manager->save($actor, $department->id, $member->id, []);
    $this->assertModelExists($emptyMembership);
});

test('rejects forged nonassignable and master rank selection', function (string $type) {
    ['actor' => $actor, 'department' => $department, 'member' => $member] = departmentManagementScenario();
    $role = $type === 'master' ? Role::findOrFail(1) : Role::factory()->create();
    $before = $member->departmentMemberships()->pluck('id')->all();

    expect(fn () => app(DepartmentMemberManager::class)->save($actor, $department->id, $member->id, [$role->id]))->toThrow(ValidationException::class);
    expect($member->departmentMemberships()->pluck('id')->all())->toBe($before);
})->with(['protected', 'master']);

test('revoked permissions are enforced after an edit modal has been opened', function () {
    ['department' => $department, 'member' => $member, 'lead' => $lead, 'rank' => $rank] = departmentManagementScenario();
    $component = Livewire::test(ManageDepartmentMembers::class)->mountAction(TestAction::make('editRanks')->table($member));
    $lead->revokePermissionTo('update-department-member-roles');
    $component->fillForm(['roles' => []])->callMountedAction();

    $this->assertDatabaseHas('department_user', ['department_id' => $department->id, 'user_id' => $member->id, 'role_id' => $rank->id]);
});

test('revoked rank availability is enforced after an edit modal has been opened', function () {
    ['department' => $department, 'member' => $member, 'rank' => $rank] = departmentManagementScenario();
    $component = Livewire::test(ManageDepartmentMembers::class)->mountAction(TestAction::make('editRanks')->table($member));
    $rank->update(['assignable_in_app' => false]);
    $component->fillForm(['roles' => [$rank->id]])->callMountedAction()->assertHasActionErrors(['roles.0' => 'in']);

    $this->assertDatabaseHas('department_user', ['department_id' => $department->id, 'user_id' => $member->id, 'role_id' => $rank->id]);
});

test('switching departments closes a pending action', function () {
    ['department' => $department, 'member' => $member, 'actor' => $actor, 'lead' => $lead] = departmentManagementScenario();
    $other = Department::factory()->create();
    $actor->departmentMemberships()->create(['department_id' => $other->id, 'role_id' => $lead->id]);

    Livewire::test(ManageDepartmentMembers::class)->set('departmentId', $department->id)
        ->mountAction(TestAction::make('editRanks')->table($member))->set('departmentId', $other->id)
        ->assertSet('mountedActions', [])->assertSet('memberActionDepartmentId', null);
});

test('warns before removing a protected rank and preserves global ranks', function () {
    ['department' => $department, 'member' => $member] = departmentManagementScenario();
    $protected = Role::factory()->create(['name' => 'Protected rank']);
    $member->assignRole($protected);
    $member->departmentMemberships()->create(['department_id' => $department->id, 'role_id' => $protected->id]);

    $component = Livewire::test(ManageDepartmentMembers::class)->mountAction(TestAction::make('removeMember')->table($member));
    expect($component->instance()->getMountedAction()->getModalDescription())
        ->toContain(__('department_members.remove_warning', ['roles' => 'Protected rank']));
    $this->assertDatabaseHas('department_user', ['department_id' => $department->id, 'user_id' => $member->id, 'role_id' => $protected->id]);
    $component->callMountedAction()->assertNotified();

    $this->assertDatabaseMissing('department_user', ['department_id' => $department->id, 'user_id' => $member->id]);
    expect($member->fresh()->hasRole($protected))->toBeTrue();
});

test('department master membership cannot be removed in the app panel', function () {
    ['actor' => $actor, 'department' => $department, 'member' => $member] = departmentManagementScenario();
    $masterMembership = $member->departmentMemberships()->create(['department_id' => $department->id, 'role_id' => 1]);

    Livewire::test(ManageDepartmentMembers::class)->assertTableActionDisabled('removeMember', $member);
    expect(fn () => app(DepartmentMemberManager::class)->remove($actor, $department->id, $member->id))->toThrow(ValidationException::class);
    $this->assertModelExists($masterMembership);
});

test('admin department table combines ranks and permits editing protected ranks with existing permissions', function () {
    ['actor' => $actor, 'department' => $department, 'member' => $member] = departmentManagementScenario();
    foreach (['access-adminpanel', 'view-any-DepartmentMember', 'update-DepartmentMember'] as $permission) {
        $actor->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $protected = Role::factory()->create();
    $member->departmentMemberships()->create(['department_id' => $department->id, 'role_id' => $protected->id]);

    Livewire::test(DepartmentMembersRelationManager::class, ['ownerRecord' => $department, 'pageClass' => EditDepartment::class])
        ->searchTable($member->name)->assertCountTableRecords(1)
        ->callAction(TestAction::make('editRanks')->table($member), ['roles' => []])->assertHasNoActionErrors();

    expect($member->departmentMemberships()->sole()->role_id)->toBeNull();
});

test('admin saves translated rank descriptions and app availability', function () {
    ['actor' => $actor, 'rank' => $rank] = departmentManagementScenario();
    foreach (['access-adminpanel', 'view-any-Role', 'view-Role', 'update-Role', 'update-role-description', 'update-role-app-assignability'] as $permission) {
        $actor->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(EditRole::class, ['record' => $rank->id])
        ->fillForm(['description' => ['de' => 'Deutsche Beschreibung', 'en' => 'English description'], 'assignable_in_app' => false])
        ->call('save')->assertHasNoFormErrors();

    $rank->refresh();
    expect($rank->getTranslations('description'))->toBe(['de' => 'Deutsche Beschreibung', 'en' => 'English description']);
    expect($rank->assignable_in_app)->toBeFalse();
    app()->setLocale('de');
    expect($rank->localizedDescription())->toBe('Deutsche Beschreibung');
    app()->setLocale('fr');
    expect($rank->localizedDescription())->toBe('English description');
});

test('admin cannot forge rank metadata without its field permission', function (string $field, mixed $value) {
    ['actor' => $actor, 'rank' => $rank] = departmentManagementScenario();
    foreach (['access-adminpanel', 'view-any-Role', 'view-Role', 'update-Role'] as $permission) {
        $actor->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(EditRole::class, ['record' => $rank->id])->set('data.'.$field, $value)->call('save')->assertForbidden();
    expect($rank->fresh()->getTranslations('description'))->toBe([]);
    expect($rank->fresh()->assignable_in_app)->toBeTrue();
})->with([
    ['description', ['en' => 'Forged']],
    ['assignable_in_app', false],
]);

test('rank descriptions and member names are escaped in the app table and modal', function () {
    ['member' => $member, 'rank' => $rank] = departmentManagementScenario();
    $payload = '<script>alert(1)</script>';
    $member->update(['name' => $payload]);
    $rank->update(['description' => ['en' => $payload, 'de' => $payload]]);

    Livewire::test(ManageDepartmentMembers::class)->assertSee($payload)->assertDontSeeHtml($payload)
        ->mountAction(TestAction::make('editRanks')->table($member))->assertSee($payload)->assertDontSeeHtml($payload);
});

test('admin can create a rank with descriptions and assignment permission', function () {
    ['actor' => $actor] = departmentManagementScenario();
    foreach (['access-adminpanel', 'create-Role', 'view-any-Role', 'view-Role', 'update-role-description', 'update-role-app-assignability'] as $permission) {
        $actor->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(CreateRole::class)->fillForm([
        'name' => 'New app rank', 'guard_name' => 'web', 'permissions' => [],
        'description' => ['de' => 'Beschreibung', 'en' => 'Description'], 'assignable_in_app' => true,
    ])->call('create')->assertHasNoFormErrors()->assertNotified();

    $role = Role::findByName('New app rank', 'web');
    expect($role->canBeAssignedInApp())->toBeTrue();
    expect($role->getTranslation('description', 'de'))->toBe('Beschreibung');
    Livewire::test(ListRoles::class)->assertCanSeeTableRecords([$role]);
});

test('ordinary admin rank edits preserve metadata without field permissions', function () {
    ['actor' => $actor, 'rank' => $rank] = departmentManagementScenario();
    foreach (['access-adminpanel', 'view-any-Role', 'view-Role', 'update-Role'] as $permission) {
        $actor->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    $rank->update(['description' => ['en' => 'Existing description']]);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(EditRole::class, ['record' => $rank->id])->fillForm(['name' => 'Renamed rank'])
        ->call('save')->assertHasNoFormErrors()->assertNotified();

    expect($rank->fresh()->name)->toBe('Renamed rank');
    expect($rank->fresh()->getTranslations('description'))->toBe(['en' => 'Existing description']);
    expect($rank->fresh()->assignable_in_app)->toBeTrue();
});

test('master cannot be edited or made assignable even by a global master', function () {
    ['actor' => $actor] = departmentManagementScenario();
    $actor->assignRole('Master');
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(EditRole::class, ['record' => 1])->assertForbidden();
    expect(Role::findOrFail(1)->canBeAssignedInApp())->toBeFalse();
});

test('rank descriptions enforce their length limit', function () {
    ['actor' => $actor, 'rank' => $rank] = departmentManagementScenario();
    foreach (['access-adminpanel', 'view-any-Role', 'view-Role', 'update-Role', 'update-role-description'] as $permission) {
        $actor->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(EditRole::class, ['record' => $rank->id])->fillForm(['description.en' => str_repeat('a', 5001)])
        ->call('save')->assertHasFormErrors(['description.en' => 'max']);
    expect($rank->fresh()->getTranslations('description'))->toBe([]);
});

test('permission migrations create management permissions without automatic grants', function () {
    $actor = User::factory()->create();
    foreach (['view-department-members', 'add-department-members', 'update-department-member-roles', 'remove-department-members', 'update-role-description', 'update-role-app-assignability', 'manage-all-department-members'] as $permission) {
        $this->assertDatabaseHas('permissions', ['name' => $permission, 'guard_name' => 'web']);
        expect($actor->can($permission))->toBeFalse();
    }
});

test('removing a member revokes their cached department permissions', function () {
    ['actor' => $actor, 'department' => $department, 'member' => $member, 'rank' => $rank] = departmentManagementScenario();
    $rank->givePermissionTo(Permission::findOrCreate('update-department-member-roles', 'web'));
    expect($member->hasDepartmentRoleWithPermissionTo('update-department-member-roles', $department->id))->toBeTrue();

    app(DepartmentMemberManager::class)->remove($actor, $department->id, $member->id);

    expect($member->hasDepartmentRoleWithPermissionTo('update-department-member-roles', $department->id))->toBeFalse();
});
