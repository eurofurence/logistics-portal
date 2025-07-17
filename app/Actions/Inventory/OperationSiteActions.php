<?php
namespace App\Actions\Inventory;

use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\ItemsOperationSite;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Actions\Action;

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

    public static function getEditAction(): Action
    {
        return Action::make('edit_operation_site')
            ->icon('heroicon-o-pencil')
            ->label(__('general.edit_operation_site'))
            ->action(function ($record, array $data, Set $set, Get $get) {
                $current_id = $get('current_selected_operation_site_id');
                if ($current_id != null) {
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
            ->form(function ($record, Get $get) {
                $department = $record->connected_department();
                return [
                    TextInput::make('name')
                        ->required()
                        ->default($get('current_selected_operation_site_name'))
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
                return self::isCreate() || self::isView() || ($get('current_selected_operation_site_id') == null);
            });
    }

    public static function getAddAction(): Action
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
                Notification::make('operation_side_added')
                    ->title(__('general.added'))
                    ->success()
                    ->send();
            })
            ->form(function ($record) {
                $department = $record->connected_department();
                return [
                    TextInput::make('name')
                        ->required()
                        ->unique()
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
        return Action::make('delete_operation_site')
            ->icon('heroicon-o-trash')
            ->requiresConfirmation()
            ->label(__('general.delete_operation_site'))
            ->modalHeading(function (Get $get) {
                return __('general.delete') . ': ' . $get('current_selected_operation_site_name');
            })
            ->color('danger')
            ->action(function (Set $set, Get $get) {
                $current_id = $get('current_selected_operation_site_id');
                if ($current_id != null) {
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
            ->disabled(function (Get $get): bool {
                return self::isCreate() || self::isView() || ($get('current_selected_operation_site_id') == null);
            });
    }
}
