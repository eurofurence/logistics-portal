<?php

use App\Filament\Admin\Resources\Departments\Pages\EditDepartment;
use App\Filament\Admin\Resources\Departments\Pages\ListDepartments;
use App\Filament\App\Resources\OrderEvents\Pages\EditOrderEvent;
use App\Filament\App\Resources\OrderEvents\Pages\ListOrderEvents;
use App\Models\Bill;
use App\Models\Department;
use App\Models\DepartmentMember;
use App\Models\InventorySubCategory;
use App\Models\Item;
use App\Models\ItemsOperationSite;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\OrderRequest;
use App\Models\Permission;
use App\Models\Storage;
use App\Models\StorageDepartmentAccess;
use App\Models\User;
use App\Models\Wishlist;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('business references prevent deleting both parents including archived references', function (string $modelClass, bool $archived) {
    $department = Department::factory()->create();
    $event = OrderEvent::factory()->create();
    $reference = $modelClass::factory()->createQuietly([
        'department_id' => $department->id,
        'order_event_id' => $event->id,
        'added_by' => auth()->id(),
        'edited_by' => auth()->id(),
    ]);
    if ($archived) {
        $reference->deleteQuietly();
    }

    expect($department->delete())->toBeFalse();
    expect($event->delete())->toBeFalse();
    expect($department->forceDelete())->toBeFalse();
    expect($event->forceDelete())->toBeFalse();

    $this->assertNotSoftDeleted($department);
    $this->assertNotSoftDeleted($event);
    $this->assertModelExists($reference);
})->with([Order::class, OrderRequest::class, Bill::class])->with([false, true]);

test('department assignments prevent deleting the department', function (Closure $createReference) {
    $department = Department::factory()->create();
    $reference = $createReference($department);

    expect($department->delete())->toBeFalse();
    expect($department->forceDelete())->toBeFalse();

    $this->assertNotSoftDeleted($department);
    $this->assertModelExists($reference);
})->with([
    'member' => [fn (Department $department): Model => DepartmentMember::create(['department_id' => $department->id, 'user_id' => auth()->id(), 'role_id' => null])],
    'item' => [fn (Department $department): Model => Item::create(['name' => 'Linked item', 'department' => $department->id])],
    'operation site' => [fn (Department $department): Model => ItemsOperationSite::create(['name' => 'Linked site', 'department' => $department->id])],
    'inventory category' => [fn (Department $department): Model => InventorySubCategory::create(['name' => 'Linked category', 'department' => $department->id])],
    'managed storage' => [fn (Department $department): Model => Storage::create(['name' => 'Linked storage', 'type' => 0, 'managing_department' => $department->id])],
    'storage access' => [function (Department $department): Model {
        $storage = Storage::create(['name' => 'Other storage', 'type' => 0, 'managing_department' => Department::factory()->create()->id]);

        return StorageDepartmentAccess::create(['department' => $department->id, 'storage' => $storage->id]);
    }],
    'wishlist sharing' => [function (Department $department): Model {
        $wishlist = Wishlist::factory()->create(['user_id' => auth()->id(), 'name' => 'Linked wishlist']);
        $wishlist->sharedDepartments()->attach($department, ['access' => 'read']);

        return $wishlist;
    }],
]);

test('unlinked parents can be deleted restored and permanently deleted', function (string $modelClass) {
    $record = $modelClass::factory()->create();

    expect($record->delete())->toBeTrue();
    $this->assertSoftDeleted($record);
    expect($record->restore())->toBeTrue();
    expect($record->forceDelete())->toBeTrue();
    $this->assertModelMissing($record);
})->with([[Department::class], [OrderEvent::class]]);

test('archived inventory and managed storage still prevent department deletion', function (string $modelClass) {
    $department = Department::factory()->create();
    $reference = $modelClass::create([
        'name' => 'Archived reference',
        ...($modelClass === Item::class ? ['department' => $department->id] : ['type' => 0, 'managing_department' => $department->id]),
    ]);
    $reference->deleteQuietly();

    expect($department->delete())->toBeFalse();
    expect($department->forceDelete())->toBeFalse();

    $this->assertNotSoftDeleted($department);
    $this->assertSoftDeleted($reference);
})->with([[Item::class], [Storage::class]]);

test('unrestricted authorization cannot bypass disabled deletion or model protection', function (string $modelClass, string $listPage, string $editPage, string $panel) {
    Filament::setCurrentPanel(Filament::getPanel($panel));
    Gate::before(fn (): bool => true);
    $record = $modelClass::factory()->create();
    OrderRequest::factory()->createQuietly([
        'department_id' => $record instanceof Department ? $record->id : Department::factory()->create()->id,
        'order_event_id' => $record instanceof OrderEvent ? $record->id : OrderEvent::factory()->create()->id,
        'added_by' => auth()->id(),
        'edited_by' => auth()->id(),
    ]);

    Livewire::test($listPage)->assertTableActionDisabled('delete', $record);
    Livewire::test($editPage, ['record' => $record->id])->assertActionDisabled('delete');
    expect($record->delete())->toBeFalse();

    $this->assertNotSoftDeleted($record);
})->with([
    [Department::class, ListDepartments::class, EditDepartment::class, 'admin'],
    [OrderEvent::class, ListOrderEvents::class, EditOrderEvent::class, 'app'],
]);

test('new references prevent permanent deletion of parents already in the trash', function (string $modelClass) {
    $record = $modelClass::factory()->create();
    $record->delete();
    $department = $record instanceof Department ? $record : Department::factory()->create();
    $event = $record instanceof OrderEvent ? $record : OrderEvent::factory()->create();
    OrderRequest::factory()->createQuietly([
        'department_id' => $department->id,
        'order_event_id' => $event->id,
        'added_by' => auth()->id(),
        'edited_by' => auth()->id(),
    ]);

    expect($record->forceDelete())->toBeFalse();

    $this->assertSoftDeleted($record);
})->with([[Department::class], [OrderEvent::class]]);

test('bulk deletion skips linked parents and deletes unlinked parents', function (string $modelClass, string $pageClass, string $panel) {
    Filament::setCurrentPanel(Filament::getPanel($panel));
    $resource = class_basename($modelClass);
    foreach (['view-any-'.$resource, 'bulk-delete-'.$resource, 'access-adminpanel'] as $permission) {
        auth()->user()->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    $linked = $modelClass::factory()->create();
    $unlinked = $modelClass::factory()->create();
    OrderRequest::factory()->createQuietly([
        'department_id' => $linked instanceof Department ? $linked->id : Department::factory()->create()->id,
        'order_event_id' => $linked instanceof OrderEvent ? $linked->id : OrderEvent::factory()->create()->id,
        'added_by' => auth()->id(),
        'edited_by' => auth()->id(),
    ]);

    Livewire::test($pageClass)->callTableBulkAction('delete', [$linked, $unlinked])->assertNotified();

    $this->assertNotSoftDeleted($linked);
    $this->assertSoftDeleted($unlinked);
})->with([
    [Department::class, ListDepartments::class, 'admin'],
    [OrderEvent::class, ListOrderEvents::class, 'app'],
]);

test('references added after opening the delete dialog keep the parent intact', function (string $modelClass, string $pageClass, string $panel) {
    Filament::setCurrentPanel(Filament::getPanel($panel));
    $resource = class_basename($modelClass);
    foreach (['view-any-'.$resource, 'view-'.$resource, 'update-'.$resource, 'delete-'.$resource, 'access-adminpanel'] as $permission) {
        auth()->user()->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    $record = $modelClass::factory()->create();
    $component = Livewire::test($pageClass, ['record' => $record->id])->mountAction('delete');
    OrderRequest::factory()->createQuietly([
        'department_id' => $record instanceof Department ? $record->id : Department::factory()->create()->id,
        'order_event_id' => $record instanceof OrderEvent ? $record->id : OrderEvent::factory()->create()->id,
        'added_by' => auth()->id(),
        'edited_by' => auth()->id(),
    ]);

    $component->callMountedAction();

    $this->assertNotSoftDeleted($record);
})->with([
    [Department::class, EditDepartment::class, 'admin'],
    [OrderEvent::class, EditOrderEvent::class, 'app'],
]);
