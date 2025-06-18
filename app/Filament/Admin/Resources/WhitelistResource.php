<?php

namespace App\Filament\Admin\Resources;

use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Whitelist;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use App\Filament\Admin\Resources\WhitelistResource\Pages;

class WhitelistResource extends Resource
{
    protected static ?string $model = Whitelist::class;

    protected static ?string $navigationIcon = 'heroicon-o-check';

    protected static ?string $recordTitleAttribute = 'email';

    protected static ?string $modelLabel = null;

    protected static ?string $pluralModelLabel = null;

    public static function getPluralModelLabel(): string
    {
        return static::$pluralModelLabel = __('general.whitelist');
    }

    public static function getModelLabel(): string
    {
        return static::$modelLabel = __('general.whitelist');
    }


    public static function getNavigationGroup(): string
    {
        static::$navigationGroup = __('general.users');

        return static::$navigationGroup;
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make('email')->required()->unique()->email(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('user.name')->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(Gate::check('bulkDelete', Whitelist::class)),
                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(Gate::check('bulkRestore', Whitelist::class)),
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
            'index' => Pages\ListWhitelists::route('/'),
            'create' => Pages\CreateWhitelist::route('/create'),
            'edit' => Pages\EditWhitelist::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        if (!Auth::Check()) {
            return false;
        }

        return (Auth::user()->checkPermissionTo('access-whitelist-navigation') || Auth::user()->isSuperAdmin());
    }
}
