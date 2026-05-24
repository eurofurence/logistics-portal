<?php
namespace App\Actions\Inventory;

use App\Models\Department;
use App\Models\ItemsOperationSite;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

final class OperationSiteActions
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

    public static function getEditAction($departmentId = null): Action
    {
        return Action::make('edit_operation_site')
            ->icon('heroicon-o-pencil')
            ->label(__('general.edit_operation_site'))
            ->action(function ($record, array $data, Set $set, Get $get) {
                $current_id = $get('current_selected_operation_site_id');
                if ($current_id !== null) {
                    $operationSite = ItemsOperationSite::find($current_id);
                    if ($operationSite) {
                        $operationSite->update([
                            'name' => $data['name'],
                        ]);
                        $set('current_selected_operation_site_id', $operationSite->id);
                        $set('current_selected_operation_site_name', $data['name']);
                        $set('operation_site', $operationSite->id);
                        Notification::make('operation_side_edited')
                            ->title(__('general.saved'))
                            ->success()
                            ->send();
                    }
                }
            })
            ->schema(function ($record, Get $get) use ($departmentId) {
                $department = null;
                if ($departmentId) {
                    $department = Department::where('id', $departmentId)->get();
                } elseif ($record) {
                    $department = $record->connected_department();
                }

                return [
                    TextInput::make('name')
                        ->required()
                        ->default($get('current_selected_operation_site_name'))
                        ->maxLength(64),
                    Select::make('department')
                        ->exists('departments', 'id')
                        ->options($department ? $department->pluck('name', 'id')->toArray() : [])
                        ->default($department ? $department->value('id') : null)
                        ->required()
                        ->selectablePlaceholder(false),
                ];
            })
            ->disabled(function (Get $get): bool {
                return self::isCreate() || self::isView() || ($get('current_selected_operation_site_id') === null);
            });
    }

    public static function getAddAction($departmentId = null): Action
    {
        return Action::make('add_operation_site')
            ->icon('heroicon-o-plus')
            ->label(__('general.add_operation_site'))
            ->action(function (array $data, Set $set) {
                $operationSite = ItemsOperationSite::create([
                    'name' => $data['name'],
                    'department' => $data['department'],
                ]);
                $set('operation_site', $operationSite->id);
                $set('current_selected_operation_site_id', $operationSite->id);
                $set('current_selected_operation_site_name', $operationSite->name);
                Notification::make('operation_side_added')
                    ->title(__('general.added'))
                    ->success()
                    ->send();
            })
            ->schema(function ($record) use ($departmentId) {
                $department = null;
                if ($departmentId) {
                    $department = Department::where('id', $departmentId)->get();
                } elseif ($record) {
                    $department = $record->connected_department();
                }

                return [
                    TextInput::make('name')
                        ->required()
                        ->maxLength(64),
                    Select::make('department')
                        ->exists('departments', 'id')
                        ->options($department ? $department->pluck('name', 'id')->toArray() : [])
                        ->default($department ? $department->value('id') : null)
                        ->required()
                        ->selectablePlaceholder(false),
                ];
            })
            ->disabled(function () use ($departmentId): bool {
                if (self::isCreate() && ! $departmentId) {
                    return true;
                }

                return self::isView();
            });
    }

    public static function getDeleteAction($departmentId = null): Action
    {
        return Action::make('delete_operation_site')
            ->icon('heroicon-o-trash')
            ->requiresConfirmation()
            ->label(__('general.delete_operation_site'))
            ->modalHeading(fn (Get $get) => __('general.delete').': '.$get('current_selected_operation_site_name'))
            ->color('danger')
            ->action(function (Set $set, Get $get) {
                $current_id = $get('current_selected_operation_site_id');
                if ($current_id !== null) {
                    $operationSite = ItemsOperationSite::find($current_id);
                    if ($operationSite) {
                        $operationSite->delete();
                        $set('current_selected_operation_site_id', null);
                        $set('current_selected_operation_site_name', null);
                        $set('operation_site', null);
                        Notification::make('operation_side_deleted')
                            ->title(__('general.deleted'))
                            ->success()
                            ->send();
                    }
                }
            })
            ->disabled(fn (Get $get): bool => self::isCreate() || self::isView() || ($get('current_selected_operation_site_id') === null));
    }
}
