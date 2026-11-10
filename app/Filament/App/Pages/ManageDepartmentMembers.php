<?php

namespace App\Filament\App\Pages;

use App\Filament\DepartmentMembersTable;
use App\Services\DepartmentMemberManager;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;

class ManageDepartmentMembers extends Page implements HasTable
{
    use InteractsWithTable;

    public ?int $departmentId = null;

    #[Locked]
    public ?int $memberActionDepartmentId = null;

    protected string $view = 'filament.app.pages.manage-department-members';

    public static function getNavigationLabel(): string
    {
        return __('department_members.title');
    }

    public function getTitle(): string
    {
        return __('department_members.title');
    }

    public static function canAccess(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'app'
            && auth()->check()
            && app(DepartmentMemberManager::class)->departments(auth()->user())->exists();
    }

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null, bool $shouldGuessMissingParameters = false, ?string $configuration = null): string
    {
        return parent::getUrl($parameters, $isAbsolute, $panel ?? 'app', $tenant, $shouldGuessMissingParameters, $configuration);
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->departmentId = app(DepartmentMemberManager::class)->departments(auth()->user())->orderBy('name')->value('id');
    }

    public function departmentSelector(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('departmentId')->label(__('department_members.department'))
                ->options(fn (): array => app(DepartmentMemberManager::class)->departments(auth()->user())->orderBy('name')->pluck('name', 'id')->all())
                ->required()->searchable()->live(),
        ]);
    }

    public function updatedDepartmentId(): void
    {
        $this->mountedActions = [];
        $this->memberActionDepartmentId = null;
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return DepartmentMembersTable::configure($table, fn (): int => $this->departmentId ?? 0, false);
    }
}
