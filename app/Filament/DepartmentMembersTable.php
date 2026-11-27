<?php

namespace App\Filament;

use App\Models\DepartmentMember;
use App\Models\Role;
use App\Models\User;
use App\Services\DepartmentMemberManager;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class DepartmentMembersTable
{
    public static function configure(Table $table, Closure $departmentId, bool $admin): Table
    {
        $manager = app(DepartmentMemberManager::class);
        $permissions = [];
        $can = function (string $operation) use ($manager, $departmentId, $admin, &$permissions): bool {
            $key = $departmentId().':'.$operation;

            return $permissions[$key] ??= $manager->can(auth()->user(), $departmentId(), $operation, $admin);
        };
        $memberships = fn (User $record) => $record->departmentMemberships->where('department_id', $departmentId());
        $captureContext = function ($livewire) use ($departmentId): void {
            $livewire->memberActionDepartmentId = $departmentId();
        };
        $checkContext = function ($livewire) use ($departmentId): void {
            abort_unless($livewire->memberActionDepartmentId === $departmentId(), 403);
        };
        $roleFields = fn (): array => [
            Select::make('roles')->label(__('department_members.roles'))->multiple()->searchable()->live()
                ->options(fn (): array => $manager->roles($admin)->pluck('name', 'id')->all()),
            TextEntry::make('rank_descriptions')->label(__('department_members.rank_descriptions'))->visible(fn (Get $get): bool => filled($get('roles')))
                ->state(fn (Get $get): array => $manager->roles($admin)->whereIn('id', $get('roles') ?? [])->get()
                    ->filter(fn ($role): bool => $role->localizedDescription() !== '')
                    ->map(fn ($role): string => $role->name.': '.$role->localizedDescription())->all())->listWithLineBreaks(),
        ];

        return $table->relationship(null)
            ->query(fn (): Builder => $can('view') ? $manager->members($departmentId()) : User::query()->whereRaw('1 = 0'))
            ->recordTitleAttribute('name')->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label(__('department_members.name'))->searchable()->sortable(),
                TextColumn::make('department_ranks')->label(__('department_members.roles'))->badge()
                    ->state(fn (User $record): array => $record->departmentMemberships->pluck('role.name')->filter()->unique()->values()->all()),
                TextColumn::make('locked')->label(__('department_members.status'))->badge()
                    ->formatStateUsing(fn ($state): string => $state ? __('department_members.locked') : __('department_members.active'))
                    ->color(fn ($state): string => $state ? 'danger' : 'success'),
            ])->filters([
                SelectFilter::make('rank')->label(__('department_members.roles'))->options(fn (): array => Role::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when($data['value'] ?? null, fn (Builder $query, $roleId) => $query
                        ->whereHas('departmentMemberships', fn (Builder $members) => $members->where('department_id', $departmentId())->where('role_id', $roleId)))),
                SelectFilter::make('locked')->label(__('department_members.status'))->options([0 => __('department_members.active'), 1 => __('department_members.locked')]),
            ])->headerActions([
                Action::make('addMember')->label(__('department_members.add'))->icon('heroicon-o-user-plus')
                    ->visible(fn (): bool => $can('add'))->authorize(fn (): bool => $can('add'))->beforeFormFilled($captureContext)
                    ->schema(fn (): array => [
                        Select::make('user_id')->label(__('department_members.user'))->required()->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => User::query()->where('name', '<>', 'System')->where('name', 'like', '%'.$search.'%')
                                ->whereDoesntHave('departmentMemberships', fn (Builder $query) => $query->where('department_id', $departmentId()))
                                ->orderBy('name')->limit(50)->pluck('name', 'id')->all())
                            ->getOptionLabelUsing(fn ($value): ?string => User::query()->find($value)?->name),
                        ...$roleFields(),
                    ])->action(function (array $data, $livewire) use ($manager, $departmentId, $admin, $checkContext): void {
                        $checkContext($livewire);
                        self::write(fn () => $manager->save(auth()->user(), $departmentId(), (int) $data['user_id'], $data['roles'] ?? [], true, $admin), $livewire);
                        Notification::make()->title(__('department_members.saved'))->success()->send();
                    }),
            ])->recordActions([
                Action::make('editRanks')->label(__('department_members.edit'))->icon('heroicon-o-pencil-square')
                    ->visible(fn (): bool => $can('update'))->authorize(fn (): bool => $can('update'))->beforeFormFilled($captureContext)
                    ->fillForm(fn (User $record): array => ['roles' => $memberships($record)
                        ->filter(fn (DepartmentMember $membership): bool => $membership->role !== null && ($admin || $membership->role->canBeAssignedInApp()))
                        ->pluck('role_id')->unique()->values()->all()])
                    ->schema(fn (): array => [
                        TextEntry::make('protected_ranks')->label(__('department_members.protected_roles'))
                            ->helperText(__('department_members.protected_help'))->visible(! $admin)
                            ->state(fn (User $record): array => $memberships($record)->filter(fn (DepartmentMember $membership): bool => $membership->role !== null && ! $membership->role->canBeAssignedInApp())
                                ->pluck('role.name')->unique()->values()->all())->badge(),
                        ...$roleFields(),
                    ])->action(function (User $record, array $data, $livewire) use ($manager, $departmentId, $admin, $checkContext): void {
                        $checkContext($livewire);
                        self::write(fn () => $manager->save(auth()->user(), $departmentId(), $record->id, $data['roles'] ?? [], false, $admin), $livewire);
                        Notification::make()->title(__('department_members.saved'))->success()->send();
                    }),
                Action::make('removeMember')->label(__('department_members.remove'))->icon('heroicon-o-user-minus')->color('danger')
                    ->visible(fn (): bool => $can('remove'))->authorize(fn (): bool => $can('remove'))->mountUsing($captureContext)
                    ->disabled(fn (User $record): bool => ! $admin && $memberships($record)->contains(fn (DepartmentMember $membership): bool => $membership->role?->isMaster() ?? false))
                    ->tooltip(fn (User $record): ?string => ! $admin && $memberships($record)->contains(fn (DepartmentMember $membership): bool => $membership->role?->isMaster() ?? false) ? __('department_members.master_protected') : null)
                    ->requiresConfirmation()->modalDescription(function (User $record) use ($memberships, $admin): string {
                        $protected = $memberships($record)->filter(fn (DepartmentMember $membership): bool => $membership->role !== null && ! $membership->role->canBeAssignedInApp())->pluck('role.name')->unique()->implode(', ');

                        return __('department_members.remove_confirmation').(! $admin && $protected !== '' ? ' '.__('department_members.remove_warning', ['roles' => $protected]) : '');
                    })->action(function (User $record, $livewire) use ($manager, $departmentId, $admin, $checkContext): void {
                        $checkContext($livewire);
                        $manager->remove(auth()->user(), $departmentId(), $record->id, $admin);
                        Notification::make()->title(__('department_members.removed'))->success()->send();
                    }),
            ]);
    }

    private static function write(Closure $operation, object $livewire): void
    {
        try {
            $operation();
        } catch (ValidationException $exception) {
            $statePath = $livewire->getMountedActionSchema()->getStatePath();
            $errors = [];
            foreach ($exception->errors() as $field => $messages) {
                $errors[$statePath.'.'.$field] = $messages;
            }

            throw ValidationException::withMessages($errors);
        }
    }
}
