<?php

declare(strict_types=1);

namespace App\Filament\Imports;

use App\Models\Department;
use App\Models\Item;
use App\Models\Storage;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\Auth;

class ItemImporter extends Importer
{
    protected static ?string $model = Item::class;

    public static function getColumns(): array
    {
        return [
            // --- Tab: General ---
            ImportColumn::make('name')
                ->label(__('general.name'))
                ->requiredMapping()
                ->rules(['required', 'max:64'])
                ->examples(['Beispiel Artikel 1', 'Schwerlastregal']),

            ImportColumn::make('description')
                ->label(__('general.description'))
                ->rules(['max:10000'])
                ->examples(['Dies ist eine Beschreibung für den Beispielartikel.', 'Großes Regal für das Hauptlager.']),

            ImportColumn::make('comment')
                ->label(__('general.comment'))
                ->rules(['max:100000'])
                ->examples(['Ein wichtiger Kommentar.', 'Regalplatz A-12']),

            // --- Tab: Details ---
            ImportColumn::make('price')
                ->label(__('general.price'))
                ->numeric()
                ->rules(['nullable', 'numeric', 'min:0'])
                ->examples(['19.99', '149.50']),

            ImportColumn::make('serialnumber')
                ->label(__('general.serialnumber'))
                ->rules(['nullable', 'max:250'])
                ->examples(['SN123456789', 'SN987654321']),

            ImportColumn::make('weight')
                ->label(__('general.weight'))
                ->rules(['nullable', 'max:250'])
                ->examples(['500g', '45kg']),

            ImportColumn::make('manufacturer_barcode')
                ->label(__('general.manufacturer_barcode'))
                ->rules(['nullable', 'max:255'])
                ->examples(['4000123456789', '7612345678901']),

            ImportColumn::make('url')
                ->label(__('general.url'))
                ->rules(['nullable', 'url'])
                ->examples(['https://example.com/produkt1', 'https://example.com/produkt2']),

            ImportColumn::make('due_date')
                ->label(__('general.due_date'))
                ->rules(['nullable', 'date'])
                ->examples([now()->addYear()->format('Y-m-d'), '2025-12-31']),

            ImportColumn::make('buy_date')
                ->label(__('general.buy_date'))
                ->rules(['nullable', 'date'])
                ->examples([now()->format('Y-m-d'), '2024-01-01']),

            ImportColumn::make('owner')
                ->label(__('general.owner'))
                ->rules(['nullable', 'max:10000'])
                ->examples(['Max Mustermann', 'Logistik Abteilung']),

            // --- Tab: More / Notes (Boolean Flags) ---
            ImportColumn::make('dangerous_good')
                ->label(__('general.dangerous_good'))
                ->boolean()
                ->rules(['boolean'])
                ->examples(['0', '1']),

            ImportColumn::make('big_size')
                ->label(__('general.big_size'))
                ->boolean()
                ->rules(['boolean'])
                ->examples(['0', '1']),

            ImportColumn::make('needs_truck')
                ->label(__('general.needs_truck'))
                ->boolean()
                ->rules(['boolean'])
                ->examples(['0', '1']),

            ImportColumn::make('stackable')
                ->label(__('general.stackable'))
                ->boolean()
                ->rules(['boolean'])
                ->examples(['1', '0']),

            ImportColumn::make('borrowed_item')
                ->label(__('general.borrowed_item'))
                ->boolean()
                ->rules(['boolean'])
                ->examples(['0', '1']),

            ImportColumn::make('rented_item')
                ->label(__('general.rented_item'))
                ->boolean()
                ->rules(['boolean'])
                ->examples(['0', '1']),
        ];
    }

    public static function getOptionsFormComponents(): array
    {
        return [
            Radio::make('import_mode')
                ->label(__('general.import_mode'))
                ->options([
                    'update' => __('general.import_mode_update'),
                    'create' => __('general.import_mode_create'),
                ])
                ->descriptions([
                    'update' => __('general.import_behavior_info'),
                    'create' => __('general.import_mode_create_description'),
                ])
                ->default('update')
                ->required(),

            Select::make('department_id')
                ->label(__('general.department'))
                ->options(function () {
                    if (Auth::user()->can('can-create-items-for-other-departments')) {
                        return Department::query()->pluck('name', 'id')->toArray();
                    } else {
                        return Auth::user()->getDepartmentsWithPermission('create-Item')->pluck('name', 'id')->toArray();
                    }
                })
                ->live()
                ->required(),

            Select::make('storage_id')
                ->label(__('general.storage'))
                ->options(function (Get $get) {
                    $user = Auth::user();
                    $departmentId = $get('department_id');

                    $query = Storage::query();

                    $query->where(function ($q) use ($departmentId) {
                        $q->where('type', 1); // Global storages

                        if ($departmentId) {
                            $q->orWhere(function ($q2) use ($departmentId) {
                                $q2->where('type', 2) // Department specific
                                    ->where(function ($subQ) use ($departmentId) {
                                        $subQ->where('managing_department', $departmentId)
                                            ->orWhereHas('departments', function ($deptQuery) use ($departmentId) {
                                                $deptQuery->where('department', $departmentId);
                                            });
                                    });
                            });
                        }
                    });

                    return $query->pluck('name', 'id')->toArray();
                })
                ->searchable()
                ->disabled(function (Get $get) {
                    return ! $get('department_id');
                }),
        ];
    }

    public function resolveRecord(): ?Item
    {
        $mode = $this->options['import_mode'] ?? 'update';
        $name = $this->data['name'];

        if ($mode === 'update') {
            $item = Item::firstOrNew([
                'name' => $name,
            ]);
        } else {
            $originalName = $name;
            $counter = 1;

            // If the name already exists, we append (1), (2), etc., just like in Windows.
            while (Item::where('name', $name)->exists()) {
                $suffix = ' ('.$counter.')';
                // Trim the original name if we exceed the 64-character limit of the database
                $name = mb_substr($originalName, 0, 64 - mb_strlen($suffix)).$suffix;
                $counter++;
            }

            // Important: Overwrite the name in the import data so that Filament saves this new name
            $this->data['name'] = $name;

            $item = new Item;
        }

        // Set the department selected from the dropdown menu
        // Note: Adjust 'department_id' if the database column in your item model is named 'department'
        $item->department = $this->options['department_id'] ?? null;
        $item->storage = $this->options['storage_id'] ?? null;

        // Automatically set the meta-user columns when creating or updating
        // Since the import runs in the background via queues, Auth::id() is null here.
        // Instead, we use the user_id directly from the running import process.
        if (! $item->exists) {
            $item->added_by = $this->import->user_id ?? 1;
        }
        $item->edited_by = $this->import->user_id ?? 1;

        return $item;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = number_format($import->successful_rows).' '.__('general.row_where_imported').'.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.__('general.row_where_failed').'.';
        }

        return $body;
    }
}
