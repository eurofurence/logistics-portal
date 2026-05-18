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
use Filament\Forms\Components\Radio;

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
                $suffix = ' (' . $counter . ')';
                // Trim the original name if we exceed the 64-character limit of the database
                $name = mb_substr($originalName, 0, 64 - mb_strlen($suffix)) . $suffix;
                $counter++;
            }

            // Important: Overwrite the name in the import data so that Filament saves this new name
            $this->data['name'] = $name;

            $item = new Item();
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
        $body =  number_format($import->successful_rows) . ' ' . __('general.row_where_imported') . '.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . __('general.row_where_failed') . '.';
        }

        return $body;
    }
}
