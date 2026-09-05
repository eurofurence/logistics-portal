<?php

namespace App\Filament\Admin\Resources\TestModels\Schemas;

use App\View\Components\BarcodeInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TestModelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('data1')
                    ->disk('s3')
                    ->visibility('public'),
                BarcodeInput::make('data2')
                    ->title('abc')
                    ->icon('heroicon-m-qr-code'),
                TextInput::make('data3'),
            ]);
    }
}
