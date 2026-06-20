<?php

namespace App\Filament\App\Resources\Bills\Pages;

use App\Filament\App\Resources\Bills\BillResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewBill extends ViewRecord
{
    protected static string $resource = BillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->icon('heroicon-o-pencil'),
            DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->modalHeading(function ($record): string {
                    return __('general.delete').': '.$record->title;
                }),
            ReplicateAction::make()
                ->icon('heroicon-o-arrow-up-on-square-stack')
                ->schema([
                    TextEntry::make('duplicate_hint')
                        ->label(__('general.hint'))
                        ->state(__('general.duplicate_note_1')),
                    TextInput::make('title')
                        ->label(__('general.title'))
                        ->required()
                        ->maxLength(64)
                        ->unique(),
                ])
                ->successRedirectUrl(fn (Model $replica): string => route('filament.app.resources.bills.edit', $replica))
                ->successNotificationTitle(__('general.entry_duplicated'))
                ->beforeReplicaSaved(function (Model $replica, array $data): void {
                    $replica->fill($data);
                    $replica->status = 'open';
                }),
        ];
    }
}
