<?php

use App\Filament\App\Resources\Bills\BillResource;
use App\Models\Bill;
use App\Models\Department;
use App\Models\OrderEvent;
use App\Models\Permission;
use App\Models\User;
use Filament\Facades\Filament;

test('returns bill search details even when related records are deleted', function (bool $departmentDeleted, bool $eventDeleted, string $departmentName, string $eventName) {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('can-see-all-bills', 'web'));
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    $department = Department::factory()->create(['name' => 'Logistics', 'deleted_at' => $departmentDeleted ? '2026-01-01 00:00:00' : null]);
    $event = OrderEvent::factory()->create(['name' => 'Eurofurence', 'deleted_at' => $eventDeleted ? '2026-01-01 00:00:00' : null]);
    Bill::factory()->createQuietly([
        'title' => 'Search regression invoice',
        'department_id' => $department->id,
        'order_event_id' => $event->id,
        'added_by' => $user->id,
        'edited_by' => $user->id,
        'value' => 125.5,
        'currency' => 'EUR',
        'status' => 'open',
    ]);

    $results = BillResource::getGlobalSearchResults('Search regression invoice');

    expect($results)->toHaveCount(1);
    expect($results->first()->details)->toBe([
        __('general.department') => $departmentName,
        __('general.order_event') => $eventName,
        __('general.value') => '125.5 EUR',
        __('general.status') => 'OPEN',
    ]);
})->with([
    'existing relations' => [false, false, 'Logistics', 'Eurofurence'],
    'deleted department' => [true, false, '—', 'Eurofurence'],
    'deleted event' => [false, true, 'Logistics', '—'],
    'both deleted' => [true, true, '—', '—'],
]);
