<?php

use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Models\Department;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

test('saves user details while preserving multiple ranks in the same department', function () {
    $admin = User::factory()->create();
    foreach (['access-adminpanel', 'access-user-navigation', 'view-any-User', 'view-User', 'update-User'] as $permission) {
        $admin->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $user = User::factory()->create();
    $department = Department::factory()->create();
    $roles = Role::factory()->count(2)->sequence(['name' => 'Member'], ['name' => 'Lead'])->create();
    foreach ($roles as $role) {
        $user->departmentMemberships()->create(['department_id' => $department->id, 'role_id' => $role->id]);
    }
    $memberships = $user->departmentMemberships()->orderBy('id')->get()->toArray();

    Livewire::test(EditUser::class, ['record' => $user->id])
        ->fillForm(['name' => 'Updated user', 'notification_email' => 'updated@example.com'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($user->fresh()->name)->toBe('Updated user');
    expect($user->fresh()->notification_email)->toBe('updated@example.com');
    expect($user->departmentMemberships()->orderBy('id')->get()->toArray())->toBe($memberships);
});
