<?php

namespace App\Filament\App\Resources\Wishlists\RelationManagers;

use Filament\Actions\ActionGroup;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SharedUsersRelationManager extends RelationManager
{
    protected static string $relationship = 'sharedUsers';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AttachAction::make()
                    ->authorize(fn (): bool => auth()->user()->can('update', $this->getOwnerRecord())),
            ])
            ->recordActionContextMenu()
            ->recordActions([
                ActionGroup::make([
                    DetachAction::make()
                        ->authorize(fn (): bool => auth()->user()->can('update', $this->getOwnerRecord())),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make()
                        ->authorize(fn (): bool => auth()->user()->can('update', $this->getOwnerRecord())),
                ]),
            ]);
    }
}
