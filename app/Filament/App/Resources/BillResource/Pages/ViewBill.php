<?php

namespace App\Filament\App\Resources\BillResource\Pages;

use Filament\Actions;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms\Components\Placeholder;
use App\Filament\App\Resources\BillResource;

class ViewBill extends ViewRecord
{
    protected static string $resource = BillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil'),
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->modalHeading(function ($record): string {
                    return __('general.delete') . ': ' . $record->title;
                }),
            Actions\ReplicateAction::make()
                ->icon('heroicon-o-arrow-up-on-square-stack')
                ->form([
                    Placeholder::make('duplicate_hint')
                        ->label(__('general.hint'))
                        ->content(__('general.duplicate_note_1')),
                    TextInput::make('title')
                        ->label(__('general.title'))
                        ->required()
                        ->maxLength(64)
                        ->unique(),
                ])
                ->successRedirectUrl(fn(Model $replica): string => route('filament.app.resources.bills.edit', $replica))
                ->successNotificationTitle(__('general.entry_duplicated'))
                ->mutateRecordDataUsing(function (array $data): array {
                    unset($data['status']);

                    return $data;
                })
        ];
    }
}
