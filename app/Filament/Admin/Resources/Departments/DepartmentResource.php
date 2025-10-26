<?php

namespace App\Filament\Admin\Resources\Departments;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use App\Filament\Admin\Resources\Departments\Pages\ListDepartments;
use App\Filament\Admin\Resources\Departments\Pages\CreateDepartment;
use App\Filament\Admin\Resources\Departments\Pages\EditDepartment;
use Filament\Tables;
use App\Models\Department;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Gate;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Admin\Resources\DepartmentResource\Pages;
use App\Filament\Admin\Resources\Departments\RelationManagers\DepartmentMembersRelationManager;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office';

    public static function getNavigationGroup(): string
    {
        static::$navigationGroup = __('general.users');

        return static::$navigationGroup;
    }

    public static function getNavigationLabel(): string
    {
        return __('general.departments');
    }

    public static function getModelLabel(): string
    {
        return __('general.department');
    }

    public static function getPluralModelLabel(): string
    {
        return __('general.departments');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->unique(ignoreRecord: true)
                            ->label(__('general.name'))
                            ->required(),
                        TextInput::make('idp_group_id')
                            ->unique(ignoreRecord: true)
                            ->label(__('general.idp_group'))
                            ->nullable(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->searchable()
                    ->toggleable(true)
                    ->label(__('general.id')),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label(__('general.name')),
                TextColumn::make('idp_group_id')
                    ->searchable()
                    ->toggleable()
                    ->label(__('general.idp_group'))
                    ->visible(config('app.identity_mode')),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->modalHeading(function ($record): string {
                        return __('general.delete') . ': ' . $record->name;
                    }),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(Gate::check('bulkDelete', Department::class)),
                    RestoreBulkAction::make()
                        ->visible(Gate::check('bulkRestore', Department::class)),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            DepartmentMembersRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDepartments::route('/'),
            'create' => CreateDepartment::route('/create'),
            'edit' => EditDepartment::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
