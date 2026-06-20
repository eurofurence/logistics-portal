
<?php

use App\Models\Department;
use App\Models\Item;
use App\Models\User;
use App\Policies\ItemPolicy;

beforeEach(function () {
    $this->policy = new ItemPolicy;
});

it('allows super admin to view any item', function () {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('isSuperAdmin')->andReturn(true);

    expect($this->policy->viewAny($user))->toBeTrue();
});

it('allows user with correct department role to view any item', function () {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('isSuperAdmin')->andReturn(false);
    $user->shouldReceive('hasAnyDepartmentRoleWithPermissionTo')
        ->with('view-any-Item')
        ->andReturn(true);

    expect($this->policy->viewAny($user))->toBeTrue();
});

it('allows super admin to view specific item', function () {
    $user = Mockery::mock(User::class);
    $item = Mockery::mock(Item::class);
    $user->shouldReceive('isSuperAdmin')->andReturn(true);

    expect($this->policy->view($user, $item))->toBeTrue();
});

it('allows user with correct permission to view specific item', function () {
    $user = Mockery::mock(User::class);
    $item = Mockery::mock(Item::class);
    $user->shouldReceive('isSuperAdmin')->andReturn(false);
    $user->shouldReceive('checkPermissionTo')
        ->with('can-see-all_items')
        ->andReturn(true);

    expect($this->policy->view($user, $item))->toBeTrue();
});

it('allows user with correct department role to view specific item', function () {
    $user = Mockery::mock(User::class);
    $department = new Department;
    $item = Mockery::mock(Item::class);
    $item->department = $department;

    $user->shouldReceive('isSuperAdmin')->andReturn(false);
    $user->shouldReceive('checkPermissionTo')->with('can-see-all_items')->andReturn(false);
    $user->shouldReceive('hasDepartmentRoleWithPermissionTo')
        ->with('view-Item', $department)
        ->andReturn(true);

    expect($this->policy->view($user, $item))->toBeTrue();
});

it('allows super admin to create item', function () {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('isSuperAdmin')->andReturn(true);

    expect($this->policy->create($user))->toBeTrue();
});

it('allows user with correct department role to create item', function () {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('isSuperAdmin')->andReturn(false);
    $user->shouldReceive('hasAnyDepartmentRoleWithPermissionTo')
        ->with('create-Item')
        ->andReturn(true);

    expect($this->policy->create($user))->toBeTrue();
});

it('allows super admin to update item', function () {
    $user = Mockery::mock(User::class);
    $item = Mockery::mock(Item::class);
    $user->shouldReceive('isSuperAdmin')->andReturn(true);

    expect($this->policy->update($user, $item))->toBeTrue();
});

it('allows user with correct department role to update item', function () {
    $user = Mockery::mock(User::class);
    $department = new Department;
    $item = Mockery::mock(Item::class);
    $item->department = $department;

    $user->shouldReceive('isSuperAdmin')->andReturn(false);
    $user->shouldReceive('hasDepartmentRoleWithPermissionTo')
        ->with('update-Item', $department)
        ->andReturn(true);

    expect($this->policy->update($user, $item))->toBeTrue();
});

it('allows super admin to delete item', function () {
    $user = Mockery::mock(User::class);
    $item = Mockery::mock(Item::class);
    $user->shouldReceive('isSuperAdmin')->andReturn(true);

    expect($this->policy->delete($user, $item))->toBeTrue();
});

it('allows user with correct department role to delete item', function () {
    $user = Mockery::mock(User::class);
    $department = new Department;
    $item = Mockery::mock(Item::class);
    $item->department = $department;

    $user->shouldReceive('isSuperAdmin')->andReturn(false);
    $user->shouldReceive('hasDepartmentRoleWithPermissionTo')
        ->with('delete-Item', $department)
        ->andReturn(true);

    expect($this->policy->delete($user, $item))->toBeTrue();
});

it('allows super admin to restore item', function () {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('isSuperAdmin')->andReturn(true);

    expect($this->policy->restore($user))->toBeTrue();
});

it('allows user with correct department role to restore item', function () {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('isSuperAdmin')->andReturn(false);
    $user->shouldReceive('hasAnyDepartmentRoleWithPermissionTo')
        ->with('restore-Item')
        ->andReturn(true);

    expect($this->policy->restore($user))->toBeTrue();
});

it('allows super admin to replicate item', function () {
    $user = Mockery::mock(User::class);
    $item = Mockery::mock(Item::class);
    $user->shouldReceive('isSuperAdmin')->andReturn(true);

    expect($this->policy->replicate($user, $item))->toBeTrue();
});

it('allows user with correct department role to replicate item', function () {
    $user = Mockery::mock(User::class);
    $department = new Department;
    $item = Mockery::mock(Item::class);
    $item->department = $department;

    $user->shouldReceive('isSuperAdmin')->andReturn(false);
    $user->shouldReceive('hasDepartmentRoleWithPermissionTo')
        ->with('replicate-Item', $department)
        ->andReturn(true);

    expect($this->policy->replicate($user, $item))->toBeTrue();
});

it('allows super admin to force delete item', function () {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('isSuperAdmin')->andReturn(true);

    expect($this->policy->forceDelete($user))->toBeTrue();
});

it('denies non-super admin to force delete item', function () {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('isSuperAdmin')->andReturn(false);

    expect($this->policy->forceDelete($user))->toBeFalse();
});
