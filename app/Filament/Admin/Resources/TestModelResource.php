<?php

namespace App\Filament\Admin\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\TestModel;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\View\Components\BarcodeInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Admin\Resources\TestModelResource\Pages;
use App\Filament\Admin\Resources\TestModelResource\RelationManagers;

class TestModelResource extends Resource
{
    protected static ?string $model = TestModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationGroup(): string
    {
        static::$navigationGroup = __('general.settings');

        return static::$navigationGroup;
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make('data1')
                ->disk('s3')
                ->visibility('public'),
            BarcodeInput::make('data2')
            ->title('abc')
            ->icon('heroicon-m-qr-code'),
            TextInput::make('data3'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id'),
                TextColumn::make('data1'),
                TextColumn::make('data2'),
                TextColumn::make('data3'),
                TextColumn::make('data4'),
                TextColumn::make('data5'),
                TextColumn::make('data6'),
                TextColumn::make('data7'),
                TextColumn::make('created_at'),
                TextColumn::make('updated_at'),
            ])
            ->filters([
                
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTestModels::route('/'),
            'create' => Pages\CreateTestModel::route('/create'),
            'edit' => Pages\EditTestModel::route('/{record}/edit'),
        ];
    }
}
