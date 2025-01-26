<?php

namespace App\Filament\App\Resources\OrderArticleResource\Pages;

use Filament\Actions;
use Filament\Forms\Set;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\Placeholder;
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
