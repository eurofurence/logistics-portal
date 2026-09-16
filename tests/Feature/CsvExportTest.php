<?php

use App\Exports\CsvExport;
use App\Exports\InventoryItemsExport;
use App\Exports\MetroExport;
use App\Exports\OrderStandardExport;
use App\Models\Department;
use App\Models\User;
use App\Settings\GeneralSettings;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

test('exports every department with one header and preserves CSV text and zero values', function (string $exportClass, string $departmentColumn) {
    app()->setLocale('en');
    $this->actingAs(User::factory()->create());
    $firstDepartment = Department::factory()->create(['name' => 'Küche']);
    $secondDepartment = Department::factory()->create(['name' => 'Logistics']);
    $source = new $exportClass([
        'columns' => ['amount', 'dangerous_good'],
        'records' => collect([
            ['id' => 1, 'name' => 'Gläser; "groß"', 'amount' => 0, 'dangerous_good' => false, $departmentColumn => $firstDepartment->id],
            ['id' => 2, 'name' => "Line one\nLine two", 'amount' => 2, 'dangerous_good' => true, $departmentColumn => $secondDepartment->id],
        ]),
        'image' => 'https://invalid.example/logo.png',
    ], 92, 92, ['dangerous_good']);

    $csv = Excel::raw(new CsvExport($source), ExcelFormat::CSV);

    expect($csv)->toBe("\xEF\xBB\xBF".implode(PHP_EOL, [
        '"Department";"ID";"Name";"Amount";"Dangerous good"',
        '"Küche";"1";"Gläser; ""groß""";"0";"No"',
        "\"Logistics\";\"2\";\"Line one\nLine two\";\"2\";\"Yes\"",
        '',
    ]));
})->with([
    'orders' => [OrderStandardExport::class, 'department_id'],
    'inventory' => [InventoryItemsExport::class, 'department'],
]);

test('includes selected calculated order totals in CSV', function () {
    app()->setLocale('en');
    $this->actingAs(User::factory()->create());
    $department = Department::factory()->create(['name' => 'Logistics']);
    $source = new OrderStandardExport([
        'columns' => ['amount'],
        'records' => collect([
            ['id' => 1, 'name' => 'Box', 'amount' => 3, 'price_net' => 2.5, 'currency' => 'EUR', 'department_id' => $department->id],
        ]),
        'calculate_total_net' => true,
    ]);

    $csv = Excel::raw(new CsvExport($source), ExcelFormat::CSV);

    expect($csv)->toBe("\xEF\xBB\xBF".implode(PHP_EOL, [
        '"Department";"ID";"Name";"Amount";"Total Net"',
        '"Logistics";"1";"Box";"3";"7,50 EUR"',
        '',
    ]));
});

test('exports Metro headings with the same CSV encoding and delimiter', function () {
    app()->setLocale('en');
    $source = new MetroExport(new Collection);

    $csv = Excel::raw(new CsvExport($source), ExcelFormat::CSV);

    expect($csv)->toStartWith("\xEF\xBB\xBF");
    expect(str_getcsv(substr(trim($csv), 3), ';', '"', ''))->toBe($source->headings());
});

test('exports timestamps in the selected timezone with their UTC offset', function () {
    GeneralSettings::fake(['timezone' => 'America/New_York']);
    $this->actingAs(User::factory()->create());
    $department = Department::factory()->create(['name' => 'Logistics']);
    $export = new OrderStandardExport([
        'columns' => ['created_at', 'approved_at'],
        'records' => collect([
            ['id' => 1, 'name' => 'Box', 'department_id' => $department->id, 'created_at' => '2026-09-16 01:30:00', 'approved_at' => null],
        ]),
    ]);

    $csv = Excel::raw(new CsvExport($export), ExcelFormat::CSV);

    expect($csv)->toContain('"2026-09-15 21:30:00 -04:00";""');
});
