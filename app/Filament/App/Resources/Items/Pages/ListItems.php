<?php

namespace App\Filament\App\Resources\Items\Pages;

use App\Filament\App\Resources\Items\ItemResource;
use App\Filament\Imports\ItemImporter;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListItems extends ListRecords
{
    protected static string $resource = ItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(ItemImporter::class)
                ->label(__('general.import').' ('.__('general.beta').')')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray'),

            CreateAction::make()
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
