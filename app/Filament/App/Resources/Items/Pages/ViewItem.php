<?php

namespace App\Filament\App\Resources\Items\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms\Components\Placeholder;
use App\Filament\App\Resources\Items\ItemResource;

class ViewItem extends ViewRecord
{

    protected static string $resource = ItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->icon('heroicon-o-pencil'),
            DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->modalHeading(function ($record): string {
                    return __('general.delete') . ': ' . $record->name;
                }),
            ReplicateAction::make()
                ->icon('heroicon-o-arrow-up-on-square-stack')
                ->schema([
                    Placeholder::make('duplicate_hint')
                        ->label(__('general.hint'))
                        ->content(__('general.duplicate_note_1')),
                    TextInput::make('name')
                        ->label(__('general.name'))
                        ->required()
                        ->maxLength(64)
                        ->unique(),
                ])
                ->successRedirectUrl(fn(Model $replica): string => route('filament.app.resources.items.edit', $replica))
                ->successNotificationTitle(__('general.entry_duplicated'))
        ];
    }
}
