<?php

namespace App\Actions\Inventory;

use Filament\Actions\Action;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;
use App\Models\InventorySubCategory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

final class SubCategorySiteActions
{
    public static function isView(): bool
    {
        return request()->route()->getName() === 'filament.app.resources.items.view';
    }

    public static function isEdit(): bool
    {
        return request()->route()->getName() === 'filament.app.resources.items.edit';
    }

    public static function isCreate(): bool
    {
        return request()->route()->getName() === 'filament.app.resources.items.create';
    }

    public static function getEditAction(): Action
    {
        return Action::make('edit_sub_category')
            ->icon('heroicon-o-pencil')
            ->label(__('general.edit_sub_category'))
            ->action(function ($record, array $data, Set $set, Get $get) {
                $current_id = $get('current_selected_sub_category_id');
                if ($current_id != null) {
                    $subCategory = InventorySubCategory::find($current_id);
                    if ($subCategory) {
                        $subCategory->update([
                            'name' => $data['name'],
                        ]);
                        $set('current_selected_sub_category_id', $subCategory->id);
                        $set('current_selected_sub_category_name', $data['name']);
                        $set('sub_category', $subCategory->id);
                        Notification::make('sub_category_edited')
                            ->title(__('general.saved'))
                            ->success()
                            ->send();
                    }
                }
            })
            ->schema(function ($record, Get $get) {
                $department = $record->connected_department();
                return [
                    TextInput::make('name')
                        ->required()
                        ->default($get('current_selected_sub_category_name'))
                        ->maxlength(64),
                    Select::make('department')
                        ->exists('departments', 'id')
                        ->options($department->pluck('name', 'id')->toArray())
                        ->default($department->value('id'))
                        ->required()
                        ->selectablePlaceholder(false)
                ];
            })
            ->disabled(function (Get $get): bool {
                if (self::isView() || self::isCreate() || ($get('current_selected_sub_category_id') == null)) {
                    return true;
                }

                return false;
            });
    }

    public static function getAddAction(): Action
    {
        return Action::make('add_sub_category')
            ->icon('heroicon-o-plus')
            ->label(__('general.add_sub_category'))
            ->action(function (array $data, Set $set) {
                $subCategory = InventorySubCategory::create([
                    'name' => $data['name'],
                    'department' => $data['department'],
                ]);
                $set('sub_category', $subCategory->id);
                $set('current_selected_sub_category_id', $subCategory->id);
                $set('current_selected_sub_category_name', $subCategory->name);
                Notification::make('sub_category_added')
                    ->title(__('general.added'))
                    ->success()
                    ->send();
            })
            ->schema(function ($record) {
                $department = $record->connected_department();
                return [
                    TextInput::make('name')
                        ->required()
                        ->maxlength(64),
                    Select::make('department')
                        ->exists('departments', 'id')
                        ->options($department->pluck('name', 'id')->toArray())
                        ->default($department->value('id'))
                        ->required()
                        ->selectablePlaceholder(false)
                ];
            })
            ->disabled(function (): bool {
                if (self::isCreate() || self::isView()) {
                    return true;
                }
                return false;
            });
    }

    public static function getDeleteAction(): Action
    {
        return Action::make('delete_sub_category')
            ->icon('heroicon-o-trash')
            ->requiresConfirmation()
            ->label(__('general.delete_sub_category'))
            ->modalHeading(function (Get $get) {
                return __('general.delete') . ': ' . $get('current_selected_sub_category_name');
            })
            ->color('danger')
            ->action(function (Set $set, Get $get) {
                $current_id = $get('current_selected_sub_category_id');
                if ($current_id != null) {
                    $subCategory = InventorySubCategory::find($current_id);
                    if ($subCategory) {
                        $subCategory->delete();
                        $set('current_selected_sub_category_id', null);
                        $set('current_selected_sub_category_name', null);
                        $set('sub_category', null);
                        Notification::make('sub_category_deleted')
                            ->title(__('general.deleted'))
                            ->success()
                            ->send();
                    }
                }
            })
            ->disabled(function (Get $get): bool {
                return self::isCreate() || self::isView() || ($get('current_selected_sub_category_id') == null);
            });
    }
}
