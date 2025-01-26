<?php

namespace App\Filament\App\Resources;

use Filament\Tables;
use Filament\Forms\Form;
use App\Models\OrderEvent;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Gate;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
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

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
                ToggleIconColumn::make('locked')
                    ->sortable()
                    ->toggleable(true)
                    ->label(__('general.locked')),
                ToggleIconColumn::make('is_active')
                    ->sortable()
                    ->toggleable(true)
                    ->label(__('general.is_active')),
                TextColumn::make('order_deadline')
                    ->sortable()
                    ->searchable()
                    ->toggleable(true)
                    ->default(__('general.not_set'))
                    ->label(__('general.order_deadline')),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->visible(fn (OrderEvent $record): bool => Gate::allows('restore', $record) || Gate::allows('forceDelete', $record) || Gate::allows('bulkForceDelete', $record) || Gate::allows('bulkRestore', $record)),
                Tables\Filters\SelectFilter::make('locked')
                    ->options([
                        '0' => __('general.unlocked'),
                        '1' => __('general.locked'),
                    ]),
                TernaryFilter::make('is_active')
                    ->label(__('general.is_active'))
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make()
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
            'index' => Pages\ListOrderEvents::route('/'),
            'create' => Pages\CreateOrderEvent::route('/create'),
            'edit' => Pages\EditOrderEvent::route('/{record}/edit'),
        ];
    }
}
