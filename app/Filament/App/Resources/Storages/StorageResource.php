<?php

namespace App\Filament\App\Resources\Storages;

use App\Filament\App\Resources\Storages\Pages\CreateStorage;
use App\Filament\App\Resources\Storages\Pages\EditStorage;
use App\Filament\App\Resources\Storages\Pages\ListStorages;
use App\Filament\App\Resources\Storages\Pages\ViewStorage;
use App\Filament\App\Resources\Storages\Schemas\StorageForm;
use App\Filament\App\Resources\Storages\Tables\StoragesTable;
use App\Models\Storage;
use Filament\Panel;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class StorageResource extends Resource
{
    protected static ?string $model = Storage::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    public static function getNavigationGroup(): string
    {
        static::$navigationGroup = __('general.inventory');

        return static::$navigationGroup;
    }

    public static function getNavigationLabel(): string
    {
        return __('general.storage');
    }

    public static function getModelLabel(): string
    {
        return __('general.storage');
    }

    public static function getPluralModelLabel(): string
    {
        return __('general.storages');
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->name;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $query = parent::getEloquentQuery();

        if ($user->isSuperAdmin() || $user->can('can-see-all-storages')) {
            return $query;
        }

        $accessibleDepartmentIds = $user->getDepartmentsWithPermission('view-Storage')->pluck('id')->toArray();

        return $query->where(function (Builder $query) use ($accessibleDepartmentIds): void {
            $query
                ->where('type', 1)
                ->orWhereIn('managing_department', $accessibleDepartmentIds);
        });
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('general.name') => $record->name,
        ];
    }

    public static function getRoutePrefix(Panel $panel): string
    {
        return 'storage';
    }

    /**
     * Checks if the current request route corresponds to the storage view page.
     *
     * This static method determines whether the current route name matches
     * the specific route used for viewing an storage in the Filament application.
     *
     * @return bool Returns true if the current route is the storage view page, false otherwise.
     */
    public static function isView(): bool
    {
        return request()->route()->getName() === 'filament.app.resources.storages.view';
    }

    public static function form(Schema $schema): Schema
    {
        return StorageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StoragesTable::configure($table);
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
            'index' => ListStorages::route('/'),
            'create' => CreateStorage::route('/create'),
            'edit' => EditStorage::route('/{record}/edit'),
            'view' => ViewStorage::route('/{record}'),
        ];
    }
}
