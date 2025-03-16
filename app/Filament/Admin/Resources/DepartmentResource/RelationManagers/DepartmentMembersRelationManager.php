<?php

namespace App\Filament\Admin\Resources\DepartmentResource\RelationManagers;

use App\Models\User;;
use Filament\Forms\Form;
use App\Models\Department;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;

class DepartmentMembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('general.members');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
                SelectColumn::make('role')
                    ->options([
                        0 => __('general.member'),
                        1 => __('general.requestor'),
                        2 => __('general.purchaser'),
                    ])
                    ->selectablePlaceholder(false)
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options([
                        __('general.member'),
                        __('general.requestor'),
                        __('general.purchaser'),
                    ])
                    ->selectablePlaceholder(false)
                    ->multiple()
                    ->label(__('general.role')),
            ])
            ->headerActions([
                CreateAction::make()
                    ->form(fn(): array => [
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
                        Select::make('role')
                            ->options([
                                0 => __('general.member'),
                                1 => __('general.requestor'),
                                2 => __('general.purchaser'),
                            ])
                            ->label(__('general.role'))
                            ->default('0')
                            ->required()
                            ->selectablePlaceholder(false)
                    ])
                    ->modalSubmitActionLabel(__('general.add'))
                    ->modalHeading(__('general.add_member'))
                    ->modalIcon('heroicon-o-user')
                    ->modelLabel(__('general.member'))
                    ->label(__('general.add_member'))
                    ->createAnother(false),
            ])
            ->actions([
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
