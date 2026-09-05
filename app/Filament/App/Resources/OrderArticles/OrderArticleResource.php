<?php

namespace App\Filament\App\Resources\OrderArticles;

use App\Filament\App\Resources\OrderArticles\Pages\CreateOrderArticle;
use App\Filament\App\Resources\OrderArticles\Pages\EditOrderArticle;
use App\Filament\App\Resources\OrderArticles\Pages\ListOrderArticles;
use App\Filament\App\Resources\OrderArticles\Pages\ViewOrderArticle;
use App\Filament\App\Resources\OrderArticles\Schemas\OrderArticleForm;
use App\Filament\App\Resources\OrderArticles\Schemas\OrderArticleInfolist;
use App\Filament\App\Resources\OrderArticles\Tables\OrderArticlesTable;
use App\Models\OrderArticle;
use DateTime;
use DateTimeZone;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class OrderArticleResource extends Resource
{
    protected static ?string $model = OrderArticle::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    public static function getNavigationGroup(): string
    {
        static::$navigationGroup = __('general.orders');

        return static::$navigationGroup;
    }

    public static function getNavigationLabel(): string
    {
        return __('general.article_directory');
    }

    public static function getModelLabel(): string
    {
        return __('general.article');
    }

    public static function getPluralModelLabel(): string
    {
        return __('general.articles');
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->name;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('general.price') => $record->price_gross.'€',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return OrderArticleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrderArticlesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return OrderArticleInfolist::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrderArticles::route('/'),
            'create' => CreateOrderArticle::route('/create'),
            'edit' => EditOrderArticle::route('/{record}/edit'),
            'view' => ViewOrderArticle::route('{record}'),
        ];
    }

    /**
     * The function `getOrderArticleNotes` returns an array of notes related to a given order article, including
     * information about locking status and deadline.
     *
     * @param Model record The `getOrderArticleNotes` function takes a `Model` object as a parameter named `record`. It
     * checks if the record is locked and if there is a deadline set for the order article. If the record is locked, it
     * adds a note to the output array mentioning the reason for locking.
     * @return array An array of notes related to the order article, including information about whether the article is
     *               locked and the reason for it being locked, as well as the order deadline if it is set.
     */
    public static function getOrderArticleNotes(Model $record): array
    {
        $output = [];

        if ($record->locked) {
            $output[] = __('general.this_article_is_locked_because').': '.$record->locked_reason;
        }

        if (! empty($record->deadline)) {
            $deadline = new DateTime($record->deadline, new DateTimeZone('UTC'));

            // Converting to the Berlin time zone
            $deadline->setTimezone(new DateTimeZone('Europe/Berlin'));
            $formattedDeadline = $deadline->format('Y-m-d H:i:s');

            $output[] = __('general.order_deadline').': '.$formattedDeadline;
        }

        if (! empty($record->important_note)) {
            $output[] = __('general.important_note').': '.$record->important_note;
        }

        return $output;
    }
}
