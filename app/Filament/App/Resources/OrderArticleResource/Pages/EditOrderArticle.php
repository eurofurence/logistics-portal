<?php

namespace App\Filament\App\Resources\OrderArticleResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\App\Resources\OrderArticleResource;

class EditOrderArticle extends EditRecord
{
    protected static string $resource = OrderArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->modalHeading(function ($record): string {
                    return __('general.delete') . ': ' . $record->name;
                }),
            ViewAction::make(),
        ];
    }
}
