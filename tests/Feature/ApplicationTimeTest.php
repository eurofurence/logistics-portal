<?php

use App\Events\BillCreated;
use App\Filament\App\Resources\Bills\Tables\BillsTable;
use App\Filament\App\Resources\Items\Pages\CreateItem;
use App\Filament\App\Resources\Items\Schemas\ItemForm;
use App\Filament\App\Resources\Items\Tables\ItemsTable;
use App\Filament\Imports\ItemImporter;
use App\Models\Bill;
use App\Models\Department;
use App\Models\Item;
use App\Models\User;
use App\Services\ApplicationTime;
use App\Settings\GeneralSettings;
use Carbon\CarbonImmutable;
use Filament\Actions\Imports\Models\Import;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Schema;
use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;

test('uses local calendar boundaries across daylight saving changes', function (string $timezone, string $date, string $start, string $end) {
    GeneralSettings::fake(['timezone' => $timezone]);

    expect(ApplicationTime::startOfDayUtc($date)->toDateTimeString())->toBe($start);
    expect(ApplicationTime::startOfNextDayUtc($date)->toDateTimeString())->toBe($end);
})->with([
    'spring forward' => ['Europe/Berlin', '2026-03-29', '2026-03-28 23:00:00', '2026-03-29 22:00:00'],
    'fall back' => ['Europe/Berlin', '2026-10-25', '2026-10-24 22:00:00', '2026-10-25 23:00:00'],
    'negative offset' => ['America/New_York', '2026-09-16', '2026-09-16 04:00:00', '2026-09-17 04:00:00'],
    'half hour offset' => ['Asia/Kolkata', '2026-09-16', '2026-09-15 18:30:00', '2026-09-16 18:30:00'],
]);

test('displays UTC values locally without changing the original timestamp', function () {
    GeneralSettings::fake(['timezone' => 'America/New_York']);
    $timestamp = CarbonImmutable::parse('2026-09-16 01:30:00', 'UTC');

    expect(ApplicationTime::local($timestamp)->format('Y-m-d H:i:s P'))->toBe('2026-09-15 21:30:00 -04:00');
    expect($timestamp->toDateTimeString())->toBe('2026-09-16 01:30:00');
    expect(ApplicationTime::local(null))->toBeNull();
});

test('round trips date time inputs through UTC while keeping date only fields unchanged', function () {
    GeneralSettings::fake(['timezone' => 'America/New_York']);
    $timestampCast = DateTimePicker::make('ordered_at')->native(false)->getDefaultStateCasts()[0];
    $dateCast = DatePicker::make('payment_deadline')->getDefaultStateCasts()[0];

    expect($timestampCast->set('2026-09-16 01:30:00'))->toBe('2026-09-15 21:30:00');
    expect($timestampCast->get('2026-09-15 21:30:00'))->toBe('2026-09-16 01:30:00');
    expect($dateCast->set('2026-09-16'))->toBe('2026-09-16');
    expect(ApplicationTime::toUtc('2026-09-15 21:30:00')->toDateTimeString())->toBe('2026-09-16 01:30:00');
});

test('uses the selected local day for created filters and groups', function () {
    GeneralSettings::fake(['timezone' => 'Europe/Berlin']);
    $this->actingAs(User::factory()->create());
    Event::fake([BillCreated::class]);
    $before = Bill::factory()->create(['created_at' => '2026-03-28 22:59:59']);
    $first = Bill::factory()->create(['created_at' => '2026-03-28 23:00:00']);
    $last = Bill::factory()->create(['created_at' => '2026-03-29 21:59:59']);
    $after = Bill::factory()->create(['created_at' => '2026-03-29 22:00:00']);
    $undated = Bill::factory()->create(['created_at' => null]);
    $filter = collect(BillsTable::getFilters())->first(fn ($filter) => $filter->getName() === 'created_at');
    $group = collect(BillsTable::getGroups())->first(fn ($group) => $group->getId() === 'created_at');

    $filteredIds = $filter->apply(Bill::query(), ['created_from' => '2026-03-29', 'created_until' => '2026-03-29'])->pluck('id')->all();

    expect($filteredIds)->toBe([$first->id, $last->id]);
    expect($group->getStringKey($first))->toBe('2026-03-29');
    expect($group->scopeQueryByKey(Bill::query(), '2026-03-29')->pluck('id')->all())->toBe([$first->id, $last->id]);
    expect($group->scopeQueryByKey(Bill::query(), null)->pluck('id')->all())->toBe([$undated->id]);
    expect($group->scopeQuery(Bill::query(), $undated)->pluck('id')->all())->toBe([$undated->id]);

    $invertedFilter = collect(ItemsTable::getFilters())->first(fn ($filter) => $filter->getName() === 'created_at');
    expect($invertedFilter->apply(Bill::query(), ['created_from' => '2026-03-29', 'created_until' => '2026-03-29', 'invert' => true])->pluck('id')->all())->toBe([$before->id, $after->id]);
});

test('loads changed settings for the next request without changing the storage timezone', function () {
    $settings = app(GeneralSettings::class);
    $settings->timezone = 'America/New_York';
    $settings->save();
    expect(FilamentTimezone::get())->toBe('America/New_York');

    $settings->timezone = 'Asia/Tokyo';
    $settings->save();
    app()->forgetScopedInstances();

    expect(FilamentTimezone::get())->toBe('Asia/Tokyo');
    expect(config('app.timezone'))->toBe('UTC');
});

test('schedules payment reminders at eight in the selected timezone', function () {
    GeneralSettings::fake(['timezone' => 'America/New_York']);
    $this->travelTo(CarbonImmutable::parse('2026-09-16 01:00:00', 'UTC'));
    $event = collect(app(Schedule::class)->events())->first(fn ($event) => str_contains($event->command ?? '', 'bills:send-payment-reminders'));

    expect($event->nextRunDate(now())->utc()->toDateTimeString())->toBe('2026-09-16 12:00:00');
});

test('stores imported local inventory dates in UTC', function () {
    GeneralSettings::fake(['timezone' => 'Europe/Berlin']);
    $user = User::factory()->create();
    $this->actingAs($user);
    $department = Department::factory()->create();
    $importer = new ItemImporter(
        new Import(['user_id' => $user->id]),
        ['name' => 'name', 'due_date' => 'due_date', 'buy_date' => 'buy_date'],
        ['import_mode' => 'create', 'department_id' => $department->id],
    );

    $importer(['name' => 'Imported box', 'due_date' => '2026-03-29', 'buy_date' => '2026-03-29 12:00:00']);

    $item = Item::where('name', 'Imported box')->sole();
    expect($item->due_date)->toBe('2026-03-28 23:00:00');
    expect($item->buy_date)->toBe('2026-03-29 10:00:00');
});

test('round trips inventory expiry dates through their timestamp column without losing a day', function () {
    GeneralSettings::fake(['timezone' => 'Europe/Berlin']);
    $this->actingAs(User::factory()->create());
    $picker = collect(ItemForm::configure(Schema::make(app(CreateItem::class)))->getFlatComponents(withHidden: true))
        ->first(fn ($component) => $component instanceof DatePicker && $component->getName() === 'due_date');
    $cast = $picker->getDefaultStateCasts()[0];

    expect($cast->get('2026-03-29'))->toBe('2026-03-28 23:00:00');
    expect($cast->set('2026-03-28 23:00:00'))->toBe('2026-03-29');
});
