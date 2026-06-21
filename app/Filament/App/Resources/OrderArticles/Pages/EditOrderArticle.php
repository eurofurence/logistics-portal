<?php

namespace App\Filament\App\Resources\OrderArticles\Pages;

use App\Filament\App\Resources\OrderArticles\OrderArticleResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditOrderArticle extends EditRecord
{
    protected static string $resource = OrderArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->modalHeading(function ($record): string {
                    return __('general.delete').': '.$record->name;
                }),
            ViewAction::make(),
        ];
    }
}
