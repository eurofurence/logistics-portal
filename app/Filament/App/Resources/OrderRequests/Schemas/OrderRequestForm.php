<?php

namespace App\Filament\App\Resources\OrderRequests\Schemas;

use App\Models\Department;
use App\Models\OrderEvent;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class OrderRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        $moderation_active = Auth::user()->can('can-moderate-order-request');

        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextEntry::make('create_description')
                            ->state(__('general.order_request_create_decription')),
                        TextInput::make('title')
                            ->label(__('general.title'))
                            ->maxLength(250)
                            ->required(),
                        TextInput::make('quantity')
                            ->label(__('general.quantity'))
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10000000)
                            ->required()
                            ->default(0)
                            ->hint(__('general.if_unnecessary')),
                        Textarea::make('message')
                            ->label(__('general.message'))
                            ->maxLength(10000)
                            ->rows(10),
                        TextInput::make('url')
                            ->label(strtoupper(__('general.url')))
                            ->maxLength(10000)
                            ->hint(__('general.url_hint')),
                        Select::make('department_id')
                            ->label(__('general.department'))
                            ->required()
                            ->exists('departments', 'id')
                            ->options(function (): array {
                                $options = Auth::user()->can('can-create-orderRequests-for-other-departments')
                                    ? Department::withoutTrashed()->pluck('name', 'id')->toArray()
                                    : Auth::user()->departmentsWithRoles()->pluck('name', 'id')->toArray();

                                return $options;
                            })
                            ->default(function () {
                                $options = Auth::user()->can('can-create-orderRequests-for-other-departments')
                                    ? Department::withoutTrashed()->pluck('name', 'id')->toArray()
                                    : Auth::user()->departmentsWithRoles()->pluck('name', 'id')->toArray();

                                // Use the reset() function to get the first element
                                return reset($options) ?: null;
                            }),
                        Select::make('order_event_id')
                            ->label(__('general.order_event'))
                            ->required()
                            ->exists('order_events', 'id')
                            ->options(function (): array {
                                $options = Auth::user()->can('can-always-order')
                                    ? OrderEvent::withoutTrashed()->pluck('name', 'id')->toArray()
                                    : OrderEvent::where('locked', false)
                                        ->where(function ($query) {
                                            $query->whereNull('order_deadline')
                                                ->orWhere('order_deadline', '>', now());
                                        })
                                        ->withoutTrashed()
                                        ->pluck('name', 'id')
                                        ->toArray();

                                return $options;
                            })
                            ->default(function () {
                                $options = Auth::user()->can('can-always-order')
                                    ? OrderEvent::withoutTrashed()->pluck('id')->toArray()
                                    : OrderEvent::where('locked', false)
                                        ->where(function ($query) {
                                            $query->whereNull('order_deadline')
                                                ->orWhere('order_deadline', '>', now());
                                        })
                                        ->withoutTrashed()
                                        ->pluck('id')
                                        ->toArray();

                                return count($options) === 1 ? $options[0] : null;
                            }),
                        Fieldset::make(__('general.notifications'))
                            ->schema([
                                Toggle::make('status_notifications')
                                    ->label(__('general.status_has_changed'))
                                    ->default(true),
                            ]),
                    ]),
                Section::make(__('general.moderation'))
                    ->schema([
                        Textarea::make('comment')
                            ->label(__('general.comment'))
                            ->maxLength(10000),
                        Select::make('status')
                            ->label(__('general.status'))
                            ->options([
                                0 => __('general.open'),
                                1 => __('general.finished'),
                                2 => __('general.processing'),
                                3 => __('general.note'),
                                4 => __('general.checking'),
                                5 => __('general.rejected'),
                            ])
                            ->required(),
                    ])->visible($moderation_active),
            ]);
    }
}
