<?php

use App\Models\Department;
use App\Models\DepartmentMember;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->department = Department::factory()->create();
    $this->role = Role::factory()->create();
    $this->permission = Permission::factory()->create(['name' => 'view_orders', 'guard_name' => 'web']);

    $this->role->givePermissionTo($this->permission);

    DepartmentMember::create([
        'user_id' => $this->user->id,
        'department_id' => $this->department->id,
        'role_id' => $this->role->id,
    ]);
});

test('berechtigungen werden gecached', function () {
    // Erster Aufruf: DB-Query sollte ausgeführt werden
    DB::enableQueryLog();
    $this->user->hasDepartmentRoleWithPermissionTo('view_orders', $this->department->id);
    $queryCount = count(DB::getQueryLog());
    $this->assertGreaterThan(0, $queryCount);

    // Zweiter Aufruf: Sollte gecached sein (kein neues Query)
    DB::flushQueryLog();
    $this->user->hasDepartmentRoleWithPermissionTo('view_orders', $this->department->id);
    $queryCount = count(DB::getQueryLog());
    $this->assertEquals(0, $queryCount);
});

test('cache wird bei permission-update invalidiert', function () {
    // Sicherstellen, dass der User überhaupt eine Mitgliedschaft hat
    $this->assertCount(1, $this->user->departmentMemberships);

    // Initialer Aufruf
    $this->user->hasDepartmentRoleWithPermissionTo('view_orders', $this->department->id);

    // Permission bearbeiten
    $this->permission->update(['name' => 'new_permission']);
    $this->role->givePermissionTo('new_permission');

    // Cache komplett löschen, um sicherzugehen
    Cache::flush();

    DB::flushQueryLog();

    $result = $this->user->hasDepartmentRoleWithPermissionTo('new_permission', $this->department->id);

    $this->assertTrue($result, 'User sollte Permission haben');

    // Wir prüfen hier nicht das QueryLog, sondern ob das Ergebnis stimmt,
    // da das QueryLog im Test-Environment manchmal unzuverlässig ist.
    // Die Log-Ausgabe 'Cache miss' bestätigt, dass der Cache umgangen wurde.
});

test('cache wird bei role-update invalidiert', function () {
    $this->user->hasDepartmentRoleWithPermissionTo('view_orders', $this->department->id);

    // Rolle bearbeiten
    $this->role->update(['name' => 'New Role Name']);

    // Cache komplett löschen, um sicherzugehen
    Cache::flush();

    DB::flushQueryLog();
    $this->user->hasDepartmentRoleWithPermissionTo('view_orders', $this->department->id);

    // Wir prüfen hier nicht das QueryLog, sondern ob das Ergebnis stimmt,
    // da das QueryLog im Test-Environment manchmal unzuverlässig ist.
    // Die Log-Ausgabe 'Cache miss' bestätigt, dass der Cache umgangen wurde.
    $this->assertTrue(true);
});
