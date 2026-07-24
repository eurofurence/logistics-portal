<?php

namespace App\Filament\App\Resources\Bills\Schemas;

use App\Filament\App\Resources\Bills\Pages\CreateBill;
use App\Forms\Components\Timeline;
use App\Models\Department;
use App\Models\OrderEvent;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class BillForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()
                    ->schema([
                        Tab::make(__('general.general'))
                            ->icon('heroicon-o-bars-4')
                            ->schema([
                                self::getFilesSection(),
                                self::getGeneralSection(),
                                self::getExpensesSection(),
                                self::getDescriptionsSection(),
                                self::getTimestampsSection(),
                            ]),
                        Tab::make(__('timeline.status_history'))
                            ->schema([
                                Timeline::make('status_history'),
                            ])
                            ->icon('heroicon-o-clock'),
                    ])->columnSpanFull(),
            ]);
    }

    public static function getFilesSection(): SpatieMediaLibraryFileUpload
    {
        return SpatieMediaLibraryFileUpload::make('files')
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
            ->columnSpanFull()
            ->required()
            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
            ->maxSize(10000)
            ->maxFiles(5)
            ->minFiles(1)
            ->label(__('general.files'))
            ->hint(__('general.bill_scan_preffered'))
            ->imageEditor()
            ->hintIcon('heroicon-m-question-mark-circle', tooltip: __('general.file_upload_tooltip'));
    }

    public static function getGeneralSection(): Section
    {
        return Section::make()
            ->schema([
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
                        ->searchable()
                        ->exists('departments', 'id')
                        ->options(function (): array {
                            return Auth::user()->can('can-create-bills-for-other-departments')
                                ? Department::withoutTrashed()->pluck('name', 'id')->toArray()
                                : Auth::user()->getDepartmentsWithPermission('create-Bill')->pluck('name', 'id')->toArray();
                        })
                        ->afterStateHydrated(function (Select $component, $state) {
                            $options = $component->getOptions();
                            if (count($options) === 1) {
                                $component->state(array_key_first($options));
                            }
                        }),
                    Select::make('order_event_id')
                        ->label(__('general.order_event'))
                        ->required()
                        ->searchable()
                        ->exists('order_events', 'id')
                        ->options(function (): array {
                            return Auth::user()->can('can-always-order')
                                ? OrderEvent::withoutTrashed()->pluck('name', 'id')->toArray()
                                : OrderEvent::all()
                                    ->pluck('name', 'id')
                                    ->toArray();
                        })
                        ->afterStateHydrated(function (Select $component, $state) {
                            $options = $component->getOptions();
                            if (count($options) === 1) {
                                $component->state(array_key_first($options));
                            }
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
                        ->searchable()
                        ->visible(fn () => Auth::user()->can('can-change-bill-status')),
                ]),
            ])
            ->description(__('general.general'))
            ->icon('heroicon-m-information-circle');
    }

    public static function getExpensesSection(): Section
    {
        return Section::make()
            ->schema([
                Grid::make([
                    'default' => 1,
                    'sm' => 1,
                    'md' => 2,
                    'lg' => 3,
                ])->schema([
                    Fieldset::make()
                        ->schema([
                            TextInput::make('value')
                                ->required()
                                ->numeric()
                                ->label(__('general.bill_amount'))
                                ->step(0.01)
                                ->minValue(config('constants.inputs.numeric.min'))
                                ->maxValue(config('constants.inputs.numeric.max'))
                                ->hintIcon('heroicon-m-question-mark-circle', tooltip: __('general.bill_amount_tooltip')),
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
                            TextInput::make('exchange_rate')
                                ->required()
                                ->numeric()
                                ->default(1)
                                ->label(__('general.exchange_rate'))
                                ->step(0.00001)
                                ->minValue(0.00001)
                                ->maxValue(99999999.99999),
                        ])
                        ->columns(1)
                        ->columnSpan(1),
                    Fieldset::make()
                        ->schema([
                            Checkbox::make('reimbursement_to_invoice_issuer')
                                ->label(__('general.reimbursement_to_invoice_issuer'))
                                ->reactive()
                                ->hintIcon('heroicon-m-question-mark-circle', tooltip: __('general.reimbursement_to_invoice_issuer_tooltip')),
                            Textarea::make('repayment_method')
                                ->label(__('general.repayment_method'))
                                ->placeholder(__('general.repayment_method_description'))
                                ->required()
                                ->maxLength(10000)
                                ->rows(5)
                                ->hidden(
                                    fn (callable $get) => $get('reimbursement_to_invoice_issuer') === true
                                )
                                ->dehydrated(true)
                                ->hintIcon('heroicon-m-question-mark-circle', tooltip: __('general.repayment_method_tooltip')),
                        ])
                        ->columns(1)
                        ->columnSpan(1),
                    Fieldset::make()
                        ->schema([
                            TextInput::make('advance_payment_value')
                                ->nullable()
                                ->numeric()
                                ->label(__('general.advance_payment'))
                                ->columnSpan(1)
                                ->step(0.01)
                                ->minValue(0)
                                ->maxValue(config('constants.inputs.numeric.max'))
                                ->hintIcon('heroicon-m-question-mark-circle', tooltip: __('general.advance_payment_tooltip')),
                            TextInput::make('advance_payment_receiver')
                                ->nullable()
                                ->datalist(User::all(['name'])->pluck('name'))
                                ->label(__('general.advance_payment_to'))
                                ->columnSpan(1)
                                ->maxLength(255),
                        ])
                        ->columns(1)
                        ->columnSpan(1),
                ]),
            ])
            ->description(__('general.expenses'))
            ->icon('heroicon-m-currency-euro');
    }

    public static function getDescriptionsSection(): Fieldset
    {
        return Fieldset::make()
            ->schema([
                Textarea::make('description')
                    ->label(__('general.description'))
                    ->maxLength(10000)
                    ->required()
                    ->helperText(__('general.bill_description_description'))
                    ->rows(6),
                Textarea::make('comment')
                    ->label(__('general.comment'))
                    ->maxLength(100000)
                    ->helperText(__('general.comment_description'))
                    ->rows(6),
            ])
            ->columns(2);
    }

    public static function getTimestampsSection(): Fieldset
    {
        return Fieldset::make('')
            ->schema([
                TextEntry::make('added_by')
                    ->label(__('general.added_by'))
                    ->state(fn (Model $record) => $record->addedBy?->name),
                TextEntry::make('edited_by')
                    ->label(__('general.edited_by'))
                    ->state(fn (Model $record) => $record->editedBy?->name),
                TextEntry::make('created_at')
                    ->label(__('general.created_at'))
                    ->state(fn (Model $record) => Carbon::parse($record->created_at)->timezone('Europe/Berlin')),
                TextEntry::make('updated_at')
                    ->label(__('general.updated_at'))
                    ->state(fn (Model $record) => Carbon::parse($record->updated_at)->timezone('Europe/Berlin')),
            ])
            ->hiddenOn(CreateBill::class)
            ->label(__('general.timestamps_and_users'));
    }
}
