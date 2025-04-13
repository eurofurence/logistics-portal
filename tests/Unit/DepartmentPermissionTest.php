<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_department_role_with_permission_to()
    {
        // Create an authenticated user
        $authUser = User::factory()->create();
        $this->actingAs($authUser);

        // Create a user
        $user = User::factory()->create();

        // Create a department
        $department = Department::factory()->create();

        // Attach the user to the department
        $user->departments()->attach($department->id);

        // Create a role
        $role = Role::factory()->create();

        // Attach the role to the user
        $user->assignRole($role);

        // Create a permission
        $permission = Permission::factory()->create(['name' => 'access-adminpanel']);

        // Attach the permission to the role
        $role->givePermissionTo($permission);

        // Check if the user has the permission in the department
        $this->assertTrue($user->hasDepartmentRoleWithPermissionTo('access-adminpanel', $department->id));

        // Check if the user does not have a different permission in the department
        $this->assertFalse($user->hasDepartmentRoleWithPermissionTo('non-existent-permission', $department->id));
    }

    public function test_get_departments_with_permission()
    {
        // Create an authenticated user
        $authUser = User::factory()->create();
        $this->actingAs($authUser);

        // Create a user
        $user = User::factory()->create();

        // Create departments
        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();

        // Attach the user to the departments
        $user->departments()->attach([$department1->id, $department2->id]);

        // Create a role
        $role = Role::factory()->create();

        // Attach the role to the user
        $user->assignRole($role);

        // Create a permission
        $permission = Permission::factory()->create(['name' => 'access-adminpanel']);

        // Attach the permission to the role
        $role->givePermissionTo($permission);

        // Get departments where the user has the permission
        $departmentsWithPermission = $user->getDepartmentsWithPermission('access-adminpanel');

        // Check if the departments are returned correctly
        $this->assertCount(2, $departmentsWithPermission);
        $this->assertContains($department1->id, $departmentsWithPermission);
        $this->assertContains($department2->id, $departmentsWithPermission);
    }
}
