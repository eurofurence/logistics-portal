<?php

namespace App\Filament\App\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Exception;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use App\Filament\App\Resources\OrderEventResource\Pages\ListOrderEvents;
use App\Filament\App\Resources\OrderEventResource\Pages\CreateOrderEvent;
use App\Filament\App\Resources\OrderEventResource\Pages\EditOrderEvent;
use Carbon\Carbon;
use Filament\Tables;
use App\Models\OrderEvent;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Gate;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Forms\Components\DateTimePicker;
use Archilex\ToggleIconColumn\Columns\ToggleIconColumn;
use App\Filament\App\Resources\OrderEventResource\Pages;

class OrderEventResource extends Resource
{
    protected static ?string $model = OrderEvent::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): string
    {
        static::$navigationGroup = __('general.orders');

        return static::$navigationGroup;
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->name;
    }

    public static function getNavigationLabel(): string
    {
        return __('general.order_events');
    }

    public static function getModelLabel(): string
    {
        return __('general.order_event');
    }

    public static function getPluralModelLabel(): string
    {
        return __('general.order_events');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('general.id') => $record->id,
            __('general.status') => $record->locked ? __('general.locked') : __('general.unlocked'),

        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('general.informations'))
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->label(__('general.name'))
                            ->unique(ignoreRecord: true),
                        DateTimePicker::make('order_deadline')
                            ->label(__('general.order_deadline'))
                            ->nullable()
                            ->timezone('Europe/Berlin')
                            ->hint('Europe/Berlin')
                            ->seconds(false),
                    ]),
                Section::make(__('general.options'))
                    ->schema([
                        Section::make([
                            Toggle::make('locked')
                                ->label(__('general.locked'))
                                ->inline()
                                ->default(false)
                        ]),
                        Section::make([
                            Toggle::make('is_active')
                                ->inline()
                                ->default(false)
                                ->helperText(__('general.is_active_description')),
                        ])
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->toggleable(true)
                    ->sortable()
                    ->searchable()
                    ->label(__('general.id')),
                TextColumn::make('name')
                    ->sortable()
                    ->searchable()
                    ->label(__('general.name')),
                #TODO
                    /*
                IconColumn::make('status')
    ->icon(fn (string $state): Heroicon => match ($state) {
        'draft' => Heroicon::OutlinedPencil,
        'reviewing' => Heroicon::OutlinedClock,
        'published' => Heroicon::OutlinedCheckCircle,
    })
        */
    /*
                ToggleIconColumn::make('locked')
                    ->sortable()
                    ->toggleable(true)
                    ->label(__('general.locked')),
                ToggleIconColumn::make('is_active')
                    ->sortable()
                    ->toggleable(true)
                    ->label(__('general.is_active')),
                    */
                TextColumn::make('order_deadline')
                    ->sortable()
                    ->searchable()
                    ->toggleable(true)
                    ->default(__('general.not_set'))
                    ->label(__('general.order_deadline'))
                    ->formatStateUsing(function ($state) {
                        if (!$state || $state === __('general.not_set')) {
                            return __('general.not_set');
                        }

                        try {
                            return Carbon::parse($state)->setTimezone('Europe/Berlin')->format('d.m.Y H:i');
                        } catch (Exception $e) {
                            return __('general.not_set');
                        }
                    }),
            ])
            ->filters([
                TrashedFilter::make()
                    ->visible(fn(OrderEvent $record): bool => Gate::allows('restore', $record) || Gate::allows('forceDelete', $record) || Gate::allows('bulkForceDelete', $record) || Gate::allows('bulkRestore', $record)),
                SelectFilter::make('locked')
                    ->options([
                        '0' => __('general.unlocked'),
                        '1' => __('general.locked'),
                    ]),
                TernaryFilter::make('is_active')
                    ->label(__('general.is_active'))
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->modalHeading(function ($record): string {
                        return __('general.delete') . ': ' . $record->name;
                    }),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(Gate::check('bulkDelete', OrderEvent::class)),
                    RestoreBulkAction::make()
                        ->visible(Gate::check('bulkRestore', OrderEvent::class)),
                ]),
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
            'index' => ListOrderEvents::route('/'),
            'create' => CreateOrderEvent::route('/create'),
            'edit' => EditOrderEvent::route('/{record}/edit'),
        ];
    }
}
