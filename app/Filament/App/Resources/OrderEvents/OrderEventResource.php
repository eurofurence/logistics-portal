<?php

namespace App\Filament\App\Resources\OrderEvents;

use App\Filament\App\Resources\OrderEvents\Pages\CreateOrderEvent;
use App\Filament\App\Resources\OrderEvents\Pages\EditOrderEvent;
use App\Filament\App\Resources\OrderEvents\Pages\ListOrderEvents;
use App\Filament\App\Resources\OrderEvents\Schemas\OrderEventForm;
use App\Filament\App\Resources\OrderEvents\Tables\OrderEventsTable;
use App\Models\OrderEvent;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class OrderEventResource extends Resource
{
    protected static ?string $model = OrderEvent::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

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
        return OrderEventForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrderEventsTable::configure($table);
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
