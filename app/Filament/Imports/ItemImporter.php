<?php

namespace App\Filament\Imports;

use App\Models\Item;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use App\Models\Department;
use App\Models\Storage;

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
                ->rules(['required', 'max:64']),

            ImportColumn::make('description')
                ->label(__('general.description'))
                ->rules(['max:10000']),

            ImportColumn::make('comment')
                ->label(__('general.comment'))
                ->rules(['max:100000']),

            // --- Tab: Details ---
            ImportColumn::make('price')
                ->label(__('general.price'))
                ->numeric()
                ->rules(['nullable', 'numeric', 'min:0']),

            ImportColumn::make('serialnumber')
                ->label(__('general.serialnumber'))
                ->rules(['nullable', 'max:250']),

            ImportColumn::make('weight')
                ->label(__('general.weight'))
                ->rules(['nullable', 'max:250']),

            ImportColumn::make('manufacturer_barcode')
                ->label(__('general.manufacturer_barcode'))
                ->rules(['nullable', 'max:255']),

            ImportColumn::make('url')
                ->label(__('general.url'))
                ->rules(['nullable', 'url']),

            ImportColumn::make('due_date')
                ->label(__('general.due_date'))
                ->rules(['nullable', 'date']),

            ImportColumn::make('buy_date')
                ->label(__('general.buy_date'))
                ->rules(['nullable', 'date']),

            ImportColumn::make('owner')
                ->label(__('general.owner'))
                ->rules(['nullable', 'max:10000']),

            // --- Tab: More / Notes (Boolean Flags) ---
            ImportColumn::make('dangerous_good')
                ->label(__('general.dangerous_good'))
                ->boolean()
                ->rules(['boolean']),

            ImportColumn::make('big_size')
                ->label(__('general.big_size'))
                ->boolean()
                ->rules(['boolean']),

            ImportColumn::make('needs_truck')
                ->label(__('general.needs_truck'))
                ->boolean()
                ->rules(['boolean']),

            ImportColumn::make('stackable')
                ->label(__('general.stackable'))
                ->boolean()
                ->rules(['boolean']),

            ImportColumn::make('borrowed_item')
                ->label(__('general.borrowed_item'))
                ->boolean()
                ->rules(['boolean']),

            ImportColumn::make('rented_item')
                ->label(__('general.rented_item'))
                ->boolean()
                ->rules(['boolean']),
        ];
    }

    public static function getOptionsFormComponents(): array
    {
        return [
            Select::make('department_id')
                ->label(__('general.department'))
                ->options(function () {
                    $user = Auth::user();

                    #TODO: Only accessible departments should be listed here. This is currently not the case
                    return $user->departments()->pluck('name', 'departments.id');
                })
                ->required(),

            Select::make('storage_id')
                ->label(__('general.storage'))
                ->options(Storage::pluck('name', 'id'))
                ->searchable(),
        ];
    }

    public function resolveRecord(): ?Item
    {
        // Checks whether an item with the same name already exists (for updates),
        // otherwise, a new object is instantiated.
        $item = Item::firstOrNew([
            'name' => $this->data['name'],
        ]);

        // Set the department selected from the dropdown menu
        // Note: Adjust 'department_id' if the database column in your item model is named 'department'
        $item->department = $this->options['department_id'] ?? null;
        $item->storage = $this->options['storage_id'] ?? null;

        // Automatically set the meta-user columns when creating or updating
        // Da der Import über Queues im Hintergrund läuft, ist Auth::id() hier null.
        // Stattdessen nutzen wir die user_id direkt aus dem laufenden Import-Prozess.
        if (! $item->exists) {
            $item->added_by = $this->import->user_id ?? 1;
        }
        $item->edited_by = $this->import->user_id ?? 1;

        return $item;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body =  __('general.import_completed') . '. ' . number_format($import->successful_rows) . ' ' . __('general.row_where_imported') . '.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . __('general.row_where_failed') . '.';
        }

        return $body;
    }
}
