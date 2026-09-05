<?php

namespace App\Filament\App\Resources\Storages\Schemas;

use App\Filament\App\Resources\Storages\StorageResource;
use App\Models\Department;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Parfaitementweb\FilamentCountryField\Forms\Components\Country;

class StorageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tabs')
                    ->tabs([
                        Tab::make('General')
                            ->schema([
                                Grid::make([
                                    'default' => 1,
                                    'sm' => 1,
                                    'md' => 2,
                                    'lg' => 2,
                                ])
                                    ->schema([
                                        TextInput::make('name')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(64)
                                            ->label(__('general.name')),
                                        Select::make('type')
                                            ->options([
                                                1 => __('general.global'),
                                                2 => __('general.department'),
                                            ])
                                            ->default(2)
                                            ->disableOptionWhen(
                                                function (string $value): bool {
                                                    return $value == 1 && ! Auth::user()->can('can-create-global-storages');
                                                }
                                            )
                                            ->label(__('general.type'))
                                            ->required(),
                                        Select::make('managing_department')
                                            ->exists('departments', 'id')
                                            ->options(function (): array {
                                                if (StorageResource::isView()) {
                                                    return Department::query()->pluck('name', 'id')->toArray();
                                                }

                                                if (Auth::user()->can('can-create-storages-for-all-departments')) {
                                                    return Department::query()->pluck('name', 'id')->toArray();
                                                } else {
                                                    return Auth::user()->getDepartmentsWithPermission('create-Storage')->pluck('name', 'id')->toArray();
                                                }
                                            })
                                            ->searchable()
                                            ->required(true)
                                            ->label(__('general.department')),
                                        Fieldset::make('address_fieldset')
                                            ->schema([
                                                Country::make('country')
                                                    ->label(__('general.country')),
                                                TextInput::make('street')
                                                    ->maxLength(128)
                                                    ->label(__('general.street')),
                                                TextInput::make('city')
                                                    ->maxLength(128)
                                                    ->label(__('general.city')),
                                                TextInput::make('post_code')
                                                    ->maxLength(64)
                                                    ->label(__('general.post_code')),
                                            ])
                                            ->columns([
                                                'default' => 1,
                                                'sm' => 1,
                                                'md' => 2,
                                                'lg' => 2,
                                            ])
                                            ->label(__('general.address')),
                                        Fieldset::make('miscellaneous')
                                            ->schema([
                                                Textarea::make('comment')
                                                    ->nullable()
                                                    ->maxLength(10000)
                                                    ->label(__('general.comment')),
                                                Textarea::make('contact_details')
                                                    ->nullable()
                                                    ->maxLength(10000)
                                                    ->label(__('general.contact_details')),
                                            ])
                                            ->label(__('general.miscellaneous')),
                                    ]),
                            ])
                            ->label(__('general.general'))
                            ->icon('heroicon-o-bars-3-center-left'),
                        Tab::make('Access')
                            ->schema([
                                Repeater::make('departments')

                                    ->relationship('departments')
                                    ->simple(
                                        Select::make('department')
                                            ->options(Department::query()->pluck('name', 'id'))
                                    )
                                    ->defaultItems(1)
                                    ->disabled(),
                            ])
                            ->label(__('general.access'))
                            ->icon('heroicon-o-key')
                            ->visible(false),
                    ])
                    ->columnSpanFull()
                    ->persistTab(),

            ]);
    }
}
