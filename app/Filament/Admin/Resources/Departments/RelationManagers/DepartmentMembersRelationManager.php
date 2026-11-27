<?php

namespace App\Filament\Admin\Resources\Departments\RelationManagers;

use App\Filament\DepartmentMembersTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;

class DepartmentMembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    #[Locked]
    public ?int $memberActionDepartmentId = null;

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

    public function table(Table $table): Table
    {
        return DepartmentMembersTable::configure($table, fn (): int => $this->getOwnerRecord()->id, true);
    }
}
