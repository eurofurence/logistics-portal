<?php

namespace App\Filament\App\Resources\OrderArticleResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\App\Resources\OrderArticleResource;

class ListOrderArticles extends ListRecords
{
    protected static string $resource = OrderArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus-circle')
        ];
    }
}
