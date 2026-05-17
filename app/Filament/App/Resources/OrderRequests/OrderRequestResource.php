<?php

namespace App\Filament\App\Resources\OrderRequests;

use App\Filament\App\Resources\OrderRequests\Pages\CreateOrderRequest;
use App\Filament\App\Resources\OrderRequests\Pages\EditOrderRequest;
use App\Filament\App\Resources\OrderRequests\Pages\ListOrderRequests;
use App\Filament\App\Resources\OrderRequests\Pages\ViewOrderRequest;
use App\Models\Department;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\OrderRequest;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class OrderRequestResource extends Resource
{
    protected static ?string $model = OrderRequest::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedPencilSquare;

    public static function getNavigationGroup(): string
    {
        static::$navigationGroup = __('general.orders');

        return static::$navigationGroup;
    }

    public static function getNavigationLabel(): string
    {
        return __('general.my_order_request');
    }

    public static function getModelLabel(): string
    {
        return __('general.order_request');
    }

    public static function getPluralModelLabel(): string
    {
        return __('general.order_requests');
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->title;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'url'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('general.department') => $record->department->name,
            __('general.order_event') => $record->event->name,
            __('general.created_at') => $record->created_at,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        if (static::canViewAny()) {
            // Counting entries based on the active status of the relationship
            // Status 0 = open
            if (Auth::check()) {
                if (Auth::user()->can('can-moderate-order-request')) {
                    $counter = static::getModel()::where('status', 0)->whereHas('event', function ($query) {
                        $query->where('is_active', true);
                    })->count();

                    return $counter > 0 ? $counter : null;
                }
            }
        }

        return null;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        $query->when(!$user->can('can-see-all-orderRequests'), function ($query) use ($user) {
            return $query->whereIn('department_id', $user->getDepartmentsWithPermission('view-OrderRequest')->pluck('id'));
        });

        return $query;
    }

    public static function form(Schema $schema): Schema
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
                                    ->default(true)
                            ])
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
                                5 => __('general.rejected')
                            ])
                            ->required()
                    ])->visible($moderation_active)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('general.id'))
                    ->toggleable()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->label(__('general.title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('department.name')
                    ->label(__('general.department'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('event.name')
                    ->label(__('general.order_event'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->label(__('general.status'))
                    ->sortable()
                    ->toggleable()
                    ->color(fn(string $state): string => match ($state) {
                        '0' => 'warning',
                        '1' => 'success',
                        '2' => 'warning',
                        '3' => 'info',
                        '4' => 'checking',
                        '5' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn(string $state): string => match ($state) {
                        '0' => 'heroicon-o-clock',
                        '1' => 'heroicon-o-check-circle',
                        '2' => 'heroicon-o-arrow-path',
                        '3' => 'heroicon-o-bookmark',
                        '4' => 'heroicon-o-arrow-path',
                        '5' => 'heroicon-o-no-symbol',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            0 => __('general.open'),
                            1 => __('general.finished'),
                            2 => __('general.processing'),
                            3 => __('general.note'),
                            4 => __('general.checking'),
                            5 => __('general.rejected'),
                            default => 'Unknown Status',
                        };
                    }),
                TextColumn::make('addedBy.name')
                    ->label(__('general.requested_by'))
                    ->sortable()
                    ->toggleable(true, true)
            ])
            ->filters([
                TrashedFilter::make()
                    ->visible(fn(): bool => Gate::allows('restore', OrderRequest::class) || Gate::allows('forceDelete', OrderRequest::class) || Gate::allows('bulkForceDelete', OrderRequest::class) || Gate::allows('bulkRestore', OrderRequest::class)),
                SelectFilter::make('order_event_id')
                    ->label(__('general.order_event'))
                    ->options(OrderEvent::all(['id', 'name'])->pluck('name', 'id'))
                    ->default(function () {
                        $activeOrderEvent = OrderEvent::where('is_active', true)->first();

                        return $activeOrderEvent ? $activeOrderEvent->id : null;
                    }),
                SelectFilter::make('department_id')
                    ->multiple()
                    ->label(__('general.department'))
                    ->options(function (): array {
                        if (Auth::user()->can('can-see-all-orderRequests')) {
                            return Department::all()->pluck('name', 'id')->toArray();
                        } else {
                            return Auth::user()->departmentsWithRoles()->pluck('name', 'id')->toArray();
                        }
                    }),
                SelectFilter::make('status')
                    ->label(__('general.status'))
                    ->options([
                        0 => __('general.open'),
                        1 => __('general.finished'),
                        2 => __('general.processing'),
                        3 => __('general.note'),
                        4 => __('general.checking'),
                        5 => __('general.rejected'),
                    ])
            ], layout: FiltersLayout::Modal)
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make()
                        ->modalHeading(function ($record): string {
                            return __('general.delete') . ': ' . $record->title;
                        }),
                    RestoreAction::make(),
                    ViewAction::make(),
                    Action::make('open_linked_order_single')
                        ->label(__('general.open_linked_order'))
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->visible(fn(Model $record) => Order::where('order_request_id', $record->id)->count() === 1)
                        ->url(fn(Model $record) => route('filament.app.resources.orders.view', Order::where('order_request_id', $record->id)->first()->id)),
                    Action::make('open_linked_order_multiple')
                        ->label(__('general.open_linked_order'))
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->visible(fn(Model $record) => Order::where('order_request_id', $record->id)->count() > 1)
                        ->schema([
                            Select::make('order_id')
                                ->label(__('general.order'))
                                ->options(fn(Model $record) => Order::where('order_request_id', $record->id)->pluck('name', 'id'))
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (array $data) {
                            return redirect(route('filament.app.resources.orders.view', $data['order_id']));
                        })
                        ->modalHeading(__('general.open_linked_order'))
                        ->modalSubmitActionLabel(__('general.show')),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(Gate::check('bulkDelete', OrderRequest::class)),
                    RestoreBulkAction::make()
                        ->visible(Gate::check('bulkRestore', OrderRequest::class)),
                    BulkAction::make('set_status')
                        ->label(__('general.set_status'))
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data): void {
                            foreach ($records as $record) {
                                $record->update(['status' => $data['status']]);
                            }
                        })
                        ->icon('heroicon-o-ellipsis-horizontal-circle')
                        ->schema([
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
                                ->prefixIcon('heroicon-o-ellipsis-horizontal-circle')
                                ->required(),
                        ])
                        ->visible(fn () => Auth::user()->can('can-moderate-order-request')),
                ]),
            ])
            ->groups([
                Group::make('department.name')
                    ->label(__('general.department'))
                    ->collapsible(),
            ])
            ->defaultGroup('department.name');
    }

    protected function getTableRecordUrlUsing(): ?callable
    {
        return fn($record) => $this->getResource()::getUrl('view', ['record' => $record]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('general.informations'))
                    ->schema([
                        \Filament\Schemas\Components\Group::make([
                            TextEntry::make('title')
                                ->label(__('general.title')),
                        ]),
                        TextEntry::make('message')
                            ->label(__('general.message')),
                        TextEntry::make('quantity')
                            ->label(__('general.quantity')),
                        TextEntry::make('url')
                            ->label(__('general.url'))
                            ->url(fn($record) => $record->url, true)
                            ->default(__('general.not_set'))
                            ->limit(100)
                            ->visible(function ($record) {
                                return $record->url;
                            })
                    ]),
                Section::make(__('general.moderation'))
                    ->schema([
                        TextEntry::make('status')
                            ->label(__('general.status'))
                            ->badge()
                            ->icon(fn(string $state): string => match ($state) {
                                '0' => 'heroicon-o-clock',
                                '1' => 'heroicon-o-check-circle',
                                '2' => 'heroicon-o-arrow-path',
                                '3' => 'heroicon-o-bookmark',
                                '4' => 'heroicon-o-arrow-path',
                                '5' => 'heroicon-o-no-symbol',
                            })
                            ->color(fn(string $state): string => match ($state) {
                                '0' => 'warning',
                                '1' => 'success',
                                '2' => 'warning',
                                '3' => 'info',
                                '4' => 'checking',
                                '5' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn(string $state): string => match ($state) {
                                '0' => __('general.open'),
                                '1' => __('general.finished'),
                                '2' => __('general.processing'),
                                '3' => __('general.note'),
                                '4' => __('general.checking'),
                                '5' => __('general.rejected'),
                                default => 'Unknown Status',
                            }),
                        TextEntry::make('comment')
                            ->default(__('general.not_set'))
                            ->label(__('general.comment'))
                    ])
                    ->visible(true),
                Section::make(__('general.other_infos'))
                    ->schema([
                        Flex::make([
                            \Filament\Schemas\Components\Group::make([
                                TextEntry::make('addedBy.name')
                                    ->label(__('general.added_by'))
                                    ->suffix(function ($record): string|null {
                                        $roles = $record->addedBy->getRolesInDepartment($record->department_id);

                                        if (!empty($roles)) {
                                            $roleNames = array_map(function ($role) {
                                                return $role['name'];
                                            }, $roles);
                                            return ' (' . __('general.currently') . ': ' . implode(', ', $roleNames) . ')';
                                        }

                                        return null;
                                    }),
                                TextEntry::make('editedBy.name')
                                    ->label(__('general.edited_by')),
                            ]),
                            \Filament\Schemas\Components\Group::make([
                                TextEntry::make('created_at')
                                    ->label(__('general.created_at'))
                                    ->dateTime(timezone: 'Europe/Berlin'),
                                TextEntry::make('updated_at')
                                    ->label(__('general.updated_at'))
                                    ->dateTime(timezone: 'Europe/Berlin'),
                            ]),
                            \Filament\Schemas\Components\Group::make([
                                TextEntry::make('department.name')
                                    ->label(__('general.department')),
                                TextEntry::make('event.name')
                                    ->label(__('general.order_event')),
                            ]),
                        ])
                    ])
                    ->columnSpanFull()
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrderRequests::route('/'),
            'create' => CreateOrderRequest::route('/create'),
            'edit' => EditOrderRequest::route('/{record}/edit'),
            'view' => ViewOrderRequest::route('/{record}')
        ];
    }
}
