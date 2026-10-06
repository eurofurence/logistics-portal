<?php

namespace App\Filament\App\Resources\ClubMembers\Tables;

use App\Filament\App\Resources\ClubMembers\Actions\SendClubMemberEmail;
use App\Models\ClubMember;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Parfaitementweb\FilamentCountryField\Forms\Components\Country;

class ClubMembersTable
{
    public static function configure(Table $table): Table
    {
        $columns = [];
        $filters = [];
        foreach (ClubMember::TEXT_FIELDS as $field) {
            $columns[] = TextColumn::make($field)->label(__('members.'.$field))
                ->searchable(query: fn (Builder $query, string $search): Builder => $query->searchPersonalData([$field], $search))
                ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderByPersonalData([$field], $direction))
                ->limit(60)->toggleable(isToggledHiddenByDefault: ! in_array($field, ['sona_name', 'first_name', 'last_name', 'email'], true));
            if ($field !== 'country') {
                $filters[] = Filter::make($field)->label(__('members.'.$field))
                    ->schema([TextInput::make('value')->label(__('members.'.$field))->maxLength(10000)])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(filled($data['value'] ?? null), fn (Builder $query): Builder => $query->searchPersonalData([$field], $data['value'])));
            }
        }
        foreach (ClubMember::DATE_FIELDS as $field) {
            $columns[] = TextColumn::make($field)->label(__('members.'.$field))->date('d.m.Y')
                ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderByPersonalData([$field], $direction))->toggleable();
            $filters[] = Filter::make($field)->label(__('members.'.$field))->schema([
                DatePicker::make('from')->label(__('members.from')),
                DatePicker::make('until')->label(__('members.until'))->afterOrEqual('from'),
            ])->query(fn (Builder $query, array $data): Builder => $query->when(filled($data['from'] ?? null) || filled($data['until'] ?? null),
                fn (Builder $query): Builder => $query->matchingPersonalData(fn (ClubMember $member): bool => $member->{$field} !== null
                    && (blank($data['from'] ?? null) || $member->{$field}->toDateString() >= $data['from'])
                    && (blank($data['until'] ?? null) || $member->{$field}->toDateString() <= $data['until']))));
        }
        $columns[] = TextColumn::make('status')->label(__('members.status'))->badge()
            ->state(fn (ClubMember $record): string => __('members.'.$record->membershipStatus()));
        $filters = [...$filters,
            SelectFilter::make('country')->label(__('members.country'))->options(fn (): array => Country::make('country')->getOptions())->searchable()
                ->query(fn (Builder $query, array $data): Builder => $query->when($data['value'] ?? null,
                    fn (Builder $query, string $country): Builder => $query->matchingPersonalData(fn (ClubMember $member): bool => $member->country === $country))),
            SelectFilter::make('status')->label(__('members.status'))->options([
                'planned' => __('members.planned'), 'active' => __('members.active'), 'departed' => __('members.departed'),
            ])->query(fn (Builder $query, array $data): Builder => $query->when($data['value'] ?? null, fn (Builder $query, string $status): Builder => $query->membershipStatus($status))),
            TernaryFilter::make('phone_present')->label(__('members.phone_present'))->queries(
                true: fn (Builder $query): Builder => $query->matchingPersonalData(fn (ClubMember $member): bool => filled($member->phone)),
                false: fn (Builder $query): Builder => $query->matchingPersonalData(fn (ClubMember $member): bool => blank($member->phone)),
                blank: fn (Builder $query): Builder => $query,
            ),
            TernaryFilter::make('departure_present')->attribute('left_at')->label(__('members.departure_present'))->nullable(),
            TrashedFilter::make(),
        ];

        return $table->columns($columns)->filters($filters)->defaultSort(fn (Builder $query): Builder => $query->getQuery()->orders
            ? $query
            : $query->orderByPersonalData(['last_name', 'first_name']))
            ->recordActionContextMenu()
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->icon('heroicon-o-eye'),
                    EditAction::make()->icon('heroicon-o-pencil-square'),
                    SendClubMemberEmail::make(),
                    DeleteAction::make()->icon('heroicon-o-trash'),
                    RestoreAction::make()->icon('heroicon-o-arrow-uturn-left'),
                ])->label(__('members.actions'))->tooltip(__('members.actions'))->icon('heroicon-o-ellipsis-vertical'),
            ]);
    }
}
