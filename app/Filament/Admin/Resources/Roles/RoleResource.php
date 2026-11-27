<?php

namespace App\Filament\Admin\Resources\Roles;

use Althinect\FilamentSpatieRolesPermissions\Resources\RoleResource as BaseRoleResource;
use App\Filament\Admin\Resources\Roles\Pages\CreateRole;
use App\Filament\Admin\Resources\Roles\Pages\EditRole;
use App\Filament\Admin\Resources\Roles\Pages\ListRoles;
use App\Filament\Admin\Resources\Roles\Pages\ViewRole;
use App\Models\Role;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class RoleResource extends BaseRoleResource
{
    public static function getEditAuthorizationResponse(Model $record): Response
    {
        return $record->isMaster() ? Response::deny() : parent::getEditAuthorizationResponse($record);
    }

    public static function getDeleteAuthorizationResponse(Model $record): Response
    {
        return $record->isMaster() ? Response::deny() : parent::getDeleteAuthorizationResponse($record);
    }

    public static function form(Schema $schema): Schema
    {
        $schema = parent::form($schema);
        $components = $schema->getComponents();
        $fields = [];
        foreach (config('app.available_locales') as $locale => $language) {
            $fields[] = Textarea::make('description.'.$locale)
                ->label(__('department_members.description', ['language' => $language]))
                ->nullable()->maxLength(5000)->rows(4)
                ->disabled(fn (): bool => ! auth()->user()->can('update-role-description'));
        }
        $fields[] = Toggle::make('assignable_in_app')->label(__('department_members.assignable'))
            ->helperText(__('department_members.assignable_help'))->default(false)
            ->disabled(fn (?Role $record): bool => ($record?->isMaster() ?? false) || ! auth()->user()->can('update-role-app-assignability'));

        return $schema->components([
            ...$components,
            Section::make(__('department_members.roles'))->schema($fields)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        $table = parent::table($table);

        return $table->columns([
            ...$table->getColumns(),
            IconColumn::make('assignable_in_app')->label(__('department_members.assignable'))->boolean(),
        ]);
    }

    /** @param array<string, mixed> $data */
    public static function authorizeMetadata(array $data, ?Role $record = null): void
    {
        if (array_key_exists('description', $data) && ! auth()->user()->can('update-role-description')) {
            $submitted = array_filter((array) $data['description'], fn ($value): bool => $value !== null && $value !== '');
            $existing = array_filter($record?->getTranslations('description') ?? [], fn ($value): bool => $value !== null && $value !== '');
            abort_unless($submitted == $existing, 403);
        }
        if (array_key_exists('assignable_in_app', $data) && ! auth()->user()->can('update-role-app-assignability')) {
            abort_unless((bool) $data['assignable_in_app'] === (bool) ($record?->assignable_in_app ?? false), 403);
        }
        Validator::make(['data' => $data], [
            'data.description' => ['nullable', 'array:'.implode(',', array_keys(config('app.available_locales')))],
            'data.description.*' => ['nullable', 'string', 'max:5000'],
            'data.assignable_in_app' => ['sometimes', 'boolean'],
        ])->validate();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function filterMetadata(array $data): array
    {
        if (! auth()->user()->can('update-role-description')) {
            unset($data['description']);
        }
        if (! auth()->user()->can('update-role-app-assignability')) {
            unset($data['assignable_in_app']);
        }

        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
            'view' => ViewRole::route('/{record}'),
        ];
    }
}
