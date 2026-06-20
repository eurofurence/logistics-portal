<?php

namespace App\Filament\App\Resources\OrderArticles\Pages;

use App\Filament\App\Resources\OrderArticles\OrderArticleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOrderArticles extends ListRecords
{
    protected static string $resource = OrderArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
