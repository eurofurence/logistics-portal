<?php

namespace App\Filament\Admin\Resources\IdpRankSyncs;

use App\Filament\Admin\Resources\IdpRankSyncs\Pages\CreateIdpRankSync;
use App\Filament\Admin\Resources\IdpRankSyncs\Pages\EditIdpRankSync;
use App\Filament\Admin\Resources\IdpRankSyncs\Pages\ListIdpRankSyncs;
use App\Filament\Admin\Resources\IdpRankSyncs\Pages\ViewIdpRankSync;
use App\Filament\Admin\Resources\IdpRankSyncs\Schemas\IdpRankSyncForm;
use App\Filament\Admin\Resources\IdpRankSyncs\Tables\IdpRankSyncsTable;
use App\Models\IdpRankSync;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class IdpRankSyncResource extends Resource
{
    protected static ?string $model = IdpRankSync::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

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
        return IdpRankSyncForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IdpRankSyncsTable::configure($table);
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
