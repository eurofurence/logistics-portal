<?php

namespace App\Filament\Admin\Resources\TestModels;

use App\Filament\Admin\Resources\TestModels\Pages\CreateTestModel;
use App\Filament\Admin\Resources\TestModels\Pages\EditTestModel;
use App\Filament\Admin\Resources\TestModels\Pages\ListTestModels;
use App\Filament\Admin\Resources\TestModels\Schemas\TestModelForm;
use App\Filament\Admin\Resources\TestModels\Tables\TestModelsTable;
use App\Models\TestModel;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TestModelResource extends Resource
{
    protected static ?string $model = TestModel::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationGroup(): string
    {
        static::$navigationGroup = __('general.settings');

        return static::$navigationGroup;
    }

    public static function form(Schema $schema): Schema
    {
        return TestModelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TestModelsTable::configure($table);
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
            'index' => ListTestModels::route('/'),
            'create' => CreateTestModel::route('/create'),
            'edit' => EditTestModel::route('/{record}/edit'),
        ];
    }
}
