<?php

namespace App\Filament\App\Resources\ClubMembers;

use App\Filament\App\Resources\ClubMembers\Pages\CreateClubMember;
use App\Filament\App\Resources\ClubMembers\Pages\EditClubMember;
use App\Filament\App\Resources\ClubMembers\Pages\ListClubMembers;
use App\Filament\App\Resources\ClubMembers\Pages\ViewClubMember;
use App\Filament\App\Resources\ClubMembers\Schemas\ClubMemberForm;
use App\Filament\App\Resources\ClubMembers\Schemas\ClubMemberInfolist;
use App\Filament\App\Resources\ClubMembers\Tables\ClubMembersTable;
use App\Models\ClubMember;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Gate;

class ClubMemberResource extends Resource
{
    protected static ?string $model = ClubMember::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $recordTitleAttribute = 'sona_name';

    public static function getNavigationGroup(): string
    {
        return __('members.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('members.member');
    }

    public static function getPluralModelLabel(): string
    {
        return __('members.members');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['sona_name', 'first_name', 'last_name', 'email'];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return Gate::allows('viewAny', ClubMember::class) ? $query : $query->whereRaw('1 = 0');
    }

    public static function form(Schema $schema): Schema
    {
        return ClubMemberForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ClubMemberInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClubMembersTable::configure($table);
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
            'index' => ListClubMembers::route('/'),
            'create' => CreateClubMember::route('/create'),
            'view' => ViewClubMember::route('/{record}'),
            'edit' => EditClubMember::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
