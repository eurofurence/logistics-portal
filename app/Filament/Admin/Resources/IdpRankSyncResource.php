<?php

namespace App\Filament\Admin\Resources;

use Filament\Forms;
use App\Models\Role;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\IdpRankSync;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Archilex\ToggleIconColumn\Columns\ToggleIconColumn;
use App\Filament\Admin\Resources\IdpRankSyncResource\Pages;
use App\Filament\Admin\Resources\IdpRankSyncResource\RelationManagers;

class IdpRankSyncResource extends Resource
{
    protected static ?string $model = IdpRankSync::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    public static function getNavigationGroup(): string
    {
        static::$navigationGroup = __('filament-spatie-roles-permissions::filament-spatie.section.roles_and_permissions');

        return static::$navigationGroup;
    }

    public static function getNavigationLabel(): string
    {
        return __('general.idp_rank_syncs');
    }

    public static function getModelLabel(): string
    {
        return __('general.idp_rank_sync');
    }

    public static function getPluralModelLabel(): string
    {
        return __('general.idp_rank_syncs');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->unique(ignoreRecord: true)
                            ->nullable()
                            ->label(__('general.name')),
                        Select::make('local_role')
                            ->options(
                                Role::all(['id', 'name'])->pluck('name', 'id')
                            )
                            ->required()
                            ->exists(table: Role::class, column: 'id')
                            ->label(__('general.local_role')),
                        TextInput::make('idp_group')
                            ->required()
                            ->label(__('general.idp_group')),
                        Toggle::make('active')
                            ->label(__('general.is_active'))
                            ->default(false)
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('general.id'))
                    ->sortable()
                    ->toggleable(true),
                TextColumn::make('name')
                    ->label(__('general.name'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('role.name')
                    ->label(__('general.local_role')),
                TextColumn::make('idp_group')
                    ->label(__('general.idp_group')),
                ToggleIconColumn::make('active')
                    ->label(__('general.is_active'))
                    ->toggleable(true),
            ])
            ->filters([
                TrashedFilter::make()
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
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
            'index' => Pages\ListIdpRankSyncs::route('/'),
            'create' => Pages\CreateIdpRankSync::route('/create'),
            'edit' => Pages\EditIdpRankSync::route('/{record}/edit'),
            'view' => Pages\ViewIdpRankSync::route('/{record}'),
        ];
    }
}
