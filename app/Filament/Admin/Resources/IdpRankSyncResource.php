<?php

namespace App\Filament\Admin\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use App\Filament\Admin\Resources\IdpRankSyncResource\Pages\ListIdpRankSyncs;
use App\Filament\Admin\Resources\IdpRankSyncResource\Pages\CreateIdpRankSync;
use App\Filament\Admin\Resources\IdpRankSyncResource\Pages\EditIdpRankSync;
use App\Filament\Admin\Resources\IdpRankSyncResource\Pages\ViewIdpRankSync;
use App\Models\Role;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\IdpRankSync;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Gate;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\TrashedFilter;
use App\Filament\Admin\Resources\IdpRankSyncResource\Pages;

class IdpRankSyncResource extends Resource
{
    protected static ?string $model = IdpRankSync::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-path';

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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                #TODO
                    /*
                IconColumn::make('active')
                    ->label(__('general.is_active'))
                    ->sortable()
                    ->boolean()
    ->icon(fn (string $state): Heroicon => match ($state) {
        'draft' => Heroicon::OutlinedPencil,
        'reviewing' => Heroicon::OutlinedClock,
        'published' => Heroicon::OutlinedCheckCircle,
    })
        */
            ])
            ->filters([
                TrashedFilter::make()
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->modalHeading(function ($record): string {
                        return __('general.delete') . ': ' . $record->name;
                    })
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(Gate::check('bulkDelete', IdpRankSync::class)),
                    RestoreBulkAction::make()
                        ->visible(Gate::check('bulkRestore', IdpRankSync::class)),
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
            'index' => ListIdpRankSyncs::route('/'),
            'create' => CreateIdpRankSync::route('/create'),
            'edit' => EditIdpRankSync::route('/{record}/edit'),
            'view' => ViewIdpRankSync::route('/{record}'),
        ];
    }
}
