<?php

namespace App\Filament\Admin\Resources\Departments\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Models\Role;

use App\Models\User;
use App\Models\Department;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Resources\RelationManagers\RelationManager;

class DepartmentMembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('general.members');
    }

    public static function getModelLabel(): string
    {
        return __('general.member');
    }

    public static function getPluralModelLabel(): string
    {
        return __('general.members');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Empty
            ]);
    }

    public function table(Table $table): Table
    {
        $unfiltered_users = User::all()->pluck('name', 'id')->toArray(); // Beispiel-Array mit Benutzer-IDs
        $users_to_remove = [0 => 'System'];

        $filtered_users = array_diff($unfiltered_users, $users_to_remove);

        return $table
            ->recordTitleAttribute('user.name')
            ->columns([
                TextColumn::make('id')
                    ->label(__('general.id'))
                    ->sortable(),
                TextColumn::make('user.name')
                    ->searchable()
                    ->sortable(),
                SelectColumn::make('role_id')
                    ->options(
                        function () {
                            return Role::where('name', '<>', 'Master')
                                ->pluck('name', 'id')
                                ->toArray();
                        }
                    )
                    ->selectablePlaceholder(true)
            ])
            ->filters([
                SelectFilter::make('role_id')
                    ->options(
                        function () {
                            return Role::where('name', '<>', 'Master')
                                ->pluck('name', 'id')
                                ->toArray();
                        }
                    )
                    ->selectablePlaceholder(false)
                    ->multiple()
                    ->label(__('general.role')),
            ])
            ->headerActions([
                CreateAction::make()
                    ->schema(fn(): array => [
                        Select::make('department_id')
                            ->options(Department::where('id', $this->getOwnerRecord()->getAttribute('id'))->pluck('name', 'id')->toArray())
                            ->default($this->getOwnerRecord()->getAttribute('id'))
                            ->disabled(true)
                            ->required(true)
                            ->label(__('general.department')),
                        Select::make('user_id')
                            ->options($filtered_users)
                            ->searchable()
                            ->label(__('general.user'))
                            ->required(),
                        Select::make('role_id')
                            ->options(
                                function () {
                                    return Role::where('name', '<>', 'Master')
                                        ->pluck('name', 'id')
                                        ->toArray();
                                }
                            )
                            ->label(__('general.role'))
                            ->default('0')
                            ->required()
                            ->selectablePlaceholder(true)
                    ])
                    ->modalSubmitActionLabel(__('general.add'))
                    ->modalHeading(__('general.add_member'))
                    ->modalIcon('heroicon-o-user')
                    ->modelLabel(__('general.member'))
                    ->label(__('general.add_member'))
                    ->createAnother(false),
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
