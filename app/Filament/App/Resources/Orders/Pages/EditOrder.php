<?php

namespace App\Filament\App\Resources\Orders\Pages;

use App\Filament\App\Resources\Orders\OrderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->modalHeading(function ($record): string {
                    return __('general.delete').': '.$record->name;
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($data['status'] === 'ordered') {
            $data['ordered_at'] = now();
        }

        return $data;
    }
}
