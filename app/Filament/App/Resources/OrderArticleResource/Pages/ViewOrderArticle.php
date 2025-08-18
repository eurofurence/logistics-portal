<?php

namespace App\Filament\App\Resources\OrderArticleResource\Pages;

use Filament\Actions\EditAction;
use Filament\Actions;
use Filament\Actions\Action;
use App\Actions\HeaderOrderAction;
use Filament\Support\Colors\Color;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\App\Resources\OrderArticleResource;

class ViewOrderArticle extends ViewRecord
{
    protected static string $resource = OrderArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('general.back'))
                ->url(url()->previous())
                ->icon('heroicon-s-arrow-left')
                ->outlined(),
            EditAction::make()
                ->color(Color::Amber)
                ->icon('heroicon-o-pencil'),
            HeaderOrderAction::make()
        ];
    }
}
