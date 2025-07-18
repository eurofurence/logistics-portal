<?php

namespace App\Filament\App\Resources;

use Carbon\Carbon;
use Filament\Forms;
use App\Models\Bill;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Department;
use App\Models\OrderEvent;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Grouping\Group;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Tables\Actions\BulkActionGroup;
use App\Filament\App\Resources\BillResource\Pages;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

class BillResource extends Resource
{
    protected static ?string $model = Bill::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public static function getNavigationGroup(): string
    {
        static::$navigationGroup = __('general.billing');

        return static::$navigationGroup;
    }

    public static function getNavigationLabel(): string
    {
        return __('general.receipts_and_invoices');
    }

    public static function getModelLabel(): string
    {
        return __('general.billing');
    }

    public static function getPluralModelLabel(): string
    {
        return __('general.billing_plural');
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->title;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('general.department') => $record->department->name,
            __('general.order_event') => $record->event->name,
            __('general.value') => $record->value . ' ' . $record->currency,
            __('general.status') => strtoupper($record->status)
        ];
    }

    protected function getTableQuery()
    {
        return parent::getTableQuery()->select([
            'id',
            'title',
            'department_id',
            'value',
            'currency',
            'status',
            'deleted_at',
            'created_at',
            'order_event_id',
        ])
            ->with([
                'event',
                'department'
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('files')
                            ->collection('bills')
                            ->directory('bills/files')
                            ->multiple()
                            ->previewable(true)
                            ->responsiveImages(true)
                            ->panelLayout('list')
                            ->appendFiles()
                            ->openable()
                            ->downloadable()
                            ->visibility('private')
                            ->multiple()
                            ->columnSpanFull()
                            ->required()
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->maxSize(10000)
                            ->maxFiles(5)
                            ->minFiles(1)
                            ->label(__('general.files'))
                            ->hint(__('general.bill_scan_preffered'))
                            ->imageEditor(),
                        Section::make()->schema([
                            Grid::make([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 2,
                                'lg' => 2,
                            ])->schema([
                                TextInput::make('title')
                                    ->label(__('general.title'))
                                    ->maxLength(250)
                                    ->required()
                                    ->helperText(__('general.bill_title_description')),
                                Select::make('department_id')
                                    ->label(__('general.department'))
                                    ->required()
                                    ->exists('departments', 'id')
                                    ->options(function (): array {
                                        $options = Auth::user()->can('can-choose-all-departments')
                                            ? Department::withoutTrashed()->pluck('name', 'id')->toArray()
                                            : Auth::user()->departments()->withoutTrashed()->pluck('name', 'department_id')->toArray();

                                        return $options;
                                    })
                                    ->default(function () {
                                        $options = Auth::user()->can('can-choose-all-departments')
                                            ? Department::withoutTrashed()->pluck('id')->toArray()
                                            : Auth::user()->departments()->withoutTrashed()->pluck('department_id')->toArray();

                                        return count($options) === 1 ? $options[0] : null;
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
                                Select::make('status')
                                    ->options([
                                        'done' => __('general.done'),
                                        'on_hold' => __('general.on_hold'),
                                        'checking' => __('general.checking'),
                                        'processing' => __('general.processing'),
                                        'open' => __('general.open'),
                                        'rejected' => __('general.rejected'),
                                    ])
                                    ->default('open')
                                    ->required()
                                    ->visible(fn() => Auth::user()->can('can-change-bill-status')),
                            ]),
                        ])
                            ->description(__('general.general'))
                            ->icon('heroicon-m-information-circle'),
                        Section::make()->schema([
                            Grid::make([
                                'default' => 2,
                                'sm' => 2,
                                'md' => 3,
                                'lg' => 3,
                            ])->schema([
                                TextInput::make('value')
                                    ->required()
                                    ->numeric()
                                    ->label(__('general.value'))
                                    ->columnSpan(2)
                                    ->step(0.01)
                                    ->minValue(config('constants.inputs.numeric.min'))
                                    ->maxValue(config('constants.inputs.numeric.max')),
                                Select::make('currency')
                                    ->label(__('general.currency'))
                                    ->options([
                                        'EUR' => '€ - Euro',
                                        'USD' => '$ - US Dollar',
                                        'GBP' => '£ - British Pound',
                                        'JPY' => '¥ - Japanese Yen',
                                        'CHF' => 'CHF - Swiss Franc',
                                        'CAD' => '$ - Canadian Dollar',
                                        'AUD' => '$ - Australian Dollar',
                                        'NZD' => '$ - New Zealand Dollar',
                                        'CNY' => '¥ - Chinese Yuan',
                                        'INR' => '₹ - Indian Rupee',
                                        'BRL' => 'R$ - Brazilian Real',
                                        'ZAR' => 'R - South African Rand',
                                        'KRW' => '₩ - South Korean Won',
                                        'MXN' => '$ - Mexican Peso',
                                        'SEK' => 'kr - Swedish Krona',
                                        'NOK' => 'kr - Norwegian Krone',
                                        'DKK' => 'kr - Danish Krone',
                                        'PLN' => 'zł - Polish Zloty',
                                        'TRY' => '₺ - Turkish Lira',
                                        'SGD' => '$ - Singapore Dollar',
                                        'HKD' => '$ - Hong Kong Dollar',
                                        'THB' => '฿ - Thai Baht',
                                        'IDR' => 'Rp - Indonesian Rupiah',
                                        'MYR' => 'RM - Malaysian Ringgit',
                                    ])
                                    ->required()
                                    ->searchable()
                                    ->default('EUR'),
                                TextInput::make('advance_payment_value')
                                    ->nullable()
                                    ->numeric()
                                    ->label(__('general.advance_payment'))
                                    ->columnSpan(1)
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->maxValue(config('constants.inputs.numeric.max')),
                                TextInput::make('advance_payment_receiver')
                                    ->nullable()
                                    ->datalist(User::all(['name'])->pluck('name'))
                                    ->label(__('general.advance_payment_to'))
                                    ->columnSpan(1)
                                    ->maxLength(255),
                            ])
                        ])
                            ->description(__('general.expenses'))
                            ->icon('heroicon-m-currency-euro'),
                        Textarea::make('description')
                            ->label(__('general.description'))
                            ->maxLength(10000)
                            ->columnSpanFull()
                            ->required()
                            ->helperText(__('general.bill_description_description'))
                            ->rows(6),
                        Textarea::make('comment')
                            ->label(__('general.comment'))
                            ->maxLength(100000)
                            ->columnSpanFull()
                            ->helperText(__('general.comment_description')),
                        Fieldset::make('')
                            ->schema([
                                Placeholder::make('added_by')
                                    ->label(__('general.added_by'))
                                    ->content(fn(Model $record) => $record->addedBy->name),
                                Placeholder::make('edited_by')
                                    ->label(__('general.edited_by'))
                                    ->content(fn(Model $record) => $record->editedBy->name),
                                Placeholder::make('created_at')
                                    ->label(__('general.created_at'))
                                    ->content(fn(Model $record) => Carbon::parse($record->created_at)->timezone('Europe/Berlin')),
                                Placeholder::make('updated_at')
                                    ->label(__('general.updated_at'))
                                    ->content(fn(Model $record) => Carbon::parse($record->updated_at)->timezone('Europe/Berlin')),
                            ])
                            ->hiddenOn(Pages\CreateBill::class)
                            ->label(__('general.timestamps_and_users'))
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->toggleable(true, true)
                    ->sortable()
                    ->label(__('general.id')),
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
                        'done' => 'success',
                        'on_hold' => 'gray',
                        'checking' => 'checking',
                        'processing' => 'warning',
                        'open' => 'warning',
                        'rejected' => 'danger',
                    })
                    ->icon(fn(string $state): string => match ($state) {
                        'on_hold' => 'heroicon-o-clock',
                        'checking' => 'heroicon-o-arrow-path',
                        'processing' => 'heroicon-o-arrow-path',
                        'open' => 'heroicon-o-document-currency-dollar',
                        'ordered' => 'heroicon-o-shopping-cart',
                        'done' => 'heroicon-o-check',
                        'rejected' => 'heroicon-o-x-circle',
                    })
                    ->formatStateUsing(function ($state) {
                        return strtoupper(str_replace('_', ' ', $state));
                    }),
                TextColumn::make('value')
                    ->label(__('general.value'))
                    ->formatStateUsing(function ($record) {
                        $priceFormatted = number_format($record->value, 2, ',', '.');

                        $symbol = match ($record->currency) {
                            'EUR' => '€', // Euro
                            'USD' => '$', // US-Dollar
                            'GBP' => '£', // Britisches Pfund
                            'JPY' => '¥', // Japanischer Yen
                            'CHF' => 'CHF', // Schweizer Franken
                            'CAD' => '$', // Kanadischer Dollar
                            'AUD' => '$', // Australischer Dollar
                            'NZD' => '$', // Neuseeländischer Dollar
                            'CNY' => '¥', // Chinesischer Yuan
                            'INR' => '₹', // Indische Rupie
                            'BRL' => 'R$', // Brasilianischer Real
                            'ZAR' => 'R', // Südafrikanischer Rand
                            'KRW' => '₩', // Südkoreanischer Won
                            'MXN' => '$', // Mexikanischer Peso
                            'SEK' => 'kr', // Schwedische Krone
                            'NOK' => 'kr', // Norwegische Krone
                            'DKK' => 'kr', // Dänische Krone
                            'PLN' => 'zł', // Polnischer Złoty
                            'TRY' => '₺', // Türkische Lira
                            'SGD' => '$', // Singapur-Dollar
                            'HKD' => '$', // Hongkong-Dollar
                            'THB' => '฿', // Thailändischer Baht
                            'IDR' => 'Rp', // Indonesische Rupiah
                            'MYR' => 'RM', // Malaysischer Ringgit
                            default => '€', // Standard: Euro
                        };


                        return $priceFormatted . ' ' . $symbol;
                    })
                    ->sortable()
                    ->toggleable()
            ])
            ->filters([
                TrashedFilter::make()
                    ->visible(fn(Bill $record): bool => Gate::allows('restore', $record) || Gate::allows('forceDelete', $record) || Gate::allows('bulkForceDelete', $record) || Gate::allows('bulkRestore', $record)),
                Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label(__('general.created_from'))
                            ->placeholder(fn($state): string => 'Dec 18, ' . now()->subYear()->format('Y')),
                        Forms\Components\DatePicker::make('created_until')
                            ->label(__('general.created_until'))
                            ->placeholder(fn($state): string => now()->format('M d, Y')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'] ?? null,
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'] ?? null,
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators['created_from'] = __('general.created_from') . ' ' . Carbon::parse($data['created_from'])->toFormattedDateString();
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators['created_until'] = __('general.created_until') . ' ' . Carbon::parse($data['created_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
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
                        if (Auth::user()->can('can-choose-all-departments') || Auth::user()->can('can-see-all-departments')) {
                            return Department::all()->pluck('name', 'id')->toArray();
                        } else {
                            return Auth::user()->departments()->pluck('name', 'department_id')->toArray();
                        }
                    }),
                SelectFilter::make('status')
                    ->multiple()
                    ->label(__('general.status'))
                    ->options([
                        'done' => __('general.done'),
                        'on_hold' => __('general.on_hold'),
                        'checking' => __('general.checking'),
                        'processing' => __('general.processing'),
                        'open' => __('general.open'),
                        'rejected' => __('general.rejected'),
                    ]),
            ], layout: FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->actions([
                ActionGroup::make([
                    ActionGroup::make([
                        Tables\Actions\EditAction::make(),
                        Tables\Actions\DeleteAction::make()
                            ->modalHeading(function ($record): string {
                                return __('general.delete') . ': ' . $record->title;
                            }),
                        Tables\Actions\RestoreAction::make(),
                        Tables\Actions\ForceDeleteAction::make(),
                        Tables\Actions\ViewAction::make(),
                    ])->dropdown(false),
                    ActionGroup::make([
                        TableAction::make('set_status')
                            ->label(__('general.set_status'))
                            ->action(function (Model $record, array $data): void {
                                $record->update(['status' => $data['status']]);
                            })
                            ->icon('heroicon-o-ellipsis-horizontal-circle')
                            ->form([
                                Select::make('status')
                                    ->label(__('general.status'))
                                    ->options([
                                        'done' => __('general.done'),
                                        'on_hold' => __('general.on_hold'),
                                        'checking' => __('general.checking'),
                                        'processing' => __('general.processing'),
                                        'open' => __('general.open'),
                                        'rejected' => __('general.rejected'),
                                    ])
                                    ->prefixIcon('heroicon-o-ellipsis-horizontal-circle')
                                    ->required(),
                            ])
                            ->visible(fn() => Auth::user()->can('can-change-bill-status')),
                    ])->dropdown(false),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn(Bill $record): bool => Gate::allows('bulkDelete', $record)),
                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn(Bill $record): bool => Gate::allows('bulkRestore', $record)),
                ]),
            ])
            ->groups([
                Group::make('event.name')
                    ->label(__('general.order_event'))
                    ->collapsible(),
                Group::make('created_at')
                    ->label(__('general.date'))
                    ->date()
                    ->collapsible(),
                Group::make('status')
                    ->label(__('general.status'))
                    ->collapsible(),
                Group::make('department.name')
                    ->label(__('general.department'))
                    ->collapsible(),
            ])
            ->defaultGroup('event.name')
            ->deferLoading()
            ->searchDebounce('750ms')
            ->persistSortInSession();
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
            'index' => Pages\ListBills::route('/'),
            'create' => Pages\CreateBill::route('/create'),
            'edit' => Pages\EditBill::route('/{record}/edit'),
        ];
    }
}
