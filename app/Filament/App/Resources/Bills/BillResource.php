<?php

namespace App\Filament\App\Resources\Bills;

use App\Filament\App\Resources\Bills\Pages\CreateBill;
use App\Filament\App\Resources\Bills\Pages\EditBill;
use App\Filament\App\Resources\Bills\Pages\ListBills;
use App\Filament\App\Resources\Bills\Pages\ViewBill;
use App\Filament\App\Resources\Bills\Schemas\BillForm;
use App\Filament\App\Resources\Bills\Tables\BillsTable;
use App\Models\Bill;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class BillResource extends Resource
{
    protected static ?string $model = Bill::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

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
            __('general.value') => $record->value.' '.$record->currency,
            __('general.status') => strtoupper($record->status),
        ];
    }

    protected function getTableQuery()
    {
        return parent::getTableQuery()
            ->with([
                'event',
                'department',
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        $query->when(! $user->can('can-see-all-bills'), function ($query) use ($user) {
            return $query->whereIn('department_id', $user->getDepartmentsWithPermission('view-Bill')->pluck('id'));
        });

        return $query;
    }

    public static function isCreate(): bool
    {
        return request()->route()->getName() === 'filament.app.resources.bills.create';
    }

    public static function form(Schema $schema): Schema
    {
        return BillForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BillsTable::configure($table);
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
            'index' => ListBills::route('/'),
            'create' => CreateBill::route('/create'),
            'edit' => EditBill::route('/{record}/edit'),
            'view' => ViewBill::route('/{record}'),
        ];
    }
}
