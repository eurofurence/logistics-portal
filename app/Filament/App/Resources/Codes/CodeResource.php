<?php

namespace App\Filament\App\Resources\Codes;

use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\App\Resources\Codes\Pages\ListCodes;
use App\Filament\App\Resources\Codes\Pages\CreateCode;
use App\Filament\App\Resources\Codes\Pages\EditCode;
use App\Filament\App\Resources\CodeResource\Pages;
use App\Filament\App\Resources\CodeResource\RelationManagers;
use App\Models\Code;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CodeResource extends Resource
{
    protected static ?string $model = Code::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-qr-code';

    public static function getNavigationGroup(): string
    {
        static::$navigationGroup = __('general.codes');

        return static::$navigationGroup;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //Tables\Actions\DeleteBulkAction::make(), #TODO: Permissions
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
            'index' => ListCodes::route('/'),
            'create' => CreateCode::route('/create'),
            'edit' => EditCode::route('/{record}/edit'),
        ];
    }
}
