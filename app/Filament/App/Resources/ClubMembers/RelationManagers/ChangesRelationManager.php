<?php

namespace App\Filament\App\Resources\ClubMembers\RelationManagers;

use App\Models\ClubMember;
use App\Models\ClubMemberChange;
use App\Services\ApplicationTime;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ChangesRelationManager extends RelationManager
{
    protected static string $relationship = 'changes';

    public static function makeTab(): Tab
    {
        return Tab::make(__('member-portal.history'))
            ->key('member-history')
            ->icon('heroicon-o-clock')
            ->visible(fn (?ClubMember $record, Component $livewire): bool => $record?->exists
                && self::canViewForRecord($record, $livewire::class))
            ->schema([
                Livewire::make(self::class, fn (ClubMember $record, Component $livewire): array => [
                    'ownerRecord' => $record,
                    'pageClass' => $livewire::class,
                ])->key('member-history-table')->lazyLoaded(),
            ]);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('member-portal.history');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return Gate::allows('viewHistory', $ownerRecord);
    }

    public function booted(): void
    {
        Gate::authorize('viewHistory', $this->getOwnerRecord());
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('staff'))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')->label(__('member-portal.changed_at'))
                    ->dateTime('d.m.Y H:i')->timezone(fn (): string => ApplicationTime::timezone()),
                TextColumn::make('actor')->label(__('member-portal.changed_by'))
                    ->state(fn (ClubMemberChange $record): string => $record->changed_by_member
                        ? __('members.member')
                        : ($record->staff?->name ?? __('member-portal.deleted_staff'))),
                TextColumn::make('field')->label(__('member-portal.changed_field'))
                    ->formatStateUsing(fn (string $state): string => __('members.'.$state)),
                TextColumn::make('previous_value')->label(__('member-portal.previous_value'))->wrap()->placeholder('—'),
                TextColumn::make('new_value')->label(__('member-portal.new_value'))->wrap()->placeholder('—'),
            ]);
    }
}
