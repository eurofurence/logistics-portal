<?php

namespace App\Filament\App\Resources\OrderArticles\Schemas;

use App\Filament\App\Resources\OrderArticles\OrderArticleResource;
use App\Services\ApplicationTime;
use Filament\Actions\Action;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class OrderArticleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('general.note'))
                    ->schema([
                        TextEntry::make('special_notes')
                            ->label('')
                            ->default(function (Model $record) {
                                return OrderArticleResource::getOrderArticleNotes($record);
                            })
                            ->listWithLineBreaks()
                            ->bulleted()
                            ->limitList(3)
                            ->expandableLimitedList(),
                    ])
                    ->icon('heroicon-m-shield-exclamation')
                    ->iconColor('warning')
                    ->visible(function (Model $record) {
                        return $record->locked || ! empty($record->deadline) || ! empty($record->important_note);
                    })
                    ->columnSpanFull(),
                Section::make()
                    ->columns(fn (Model $record): array => ['default' => 1, 'md' => filled($record->picture) ? 2 : 1])
                    ->schema([
                        ImageEntry::make('picture_thumbnail_url')
                            ->action(Action::make('previewArticleImage')
                                ->modalHeading(__('general.picture'))
                                ->modalSubmitAction(false)
                                ->modalContent(fn (Model $record): View => view('filament.app.articles.image-preview', ['picture' => $record->picture_url])))
                            ->extraImgAttributes([
                                'class' => 'rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900',
                                'style' => 'object-fit: contain; max-width: 100%',
                            ], true)
                            ->label(__('general.picture'))
                            ->hiddenLabel()
                            ->visible(function (Model $record) {
                                return $record->picture;
                            })
                            ->imageWidth('100%')
                            ->imageHeight('320px'),
                        Group::make([
                            TextEntry::make('name')
                                ->hiddenLabel()
                                ->extraAttributes(['style' => 'overflow-wrap: anywhere'])
                                ->size(TextSize::Medium)
                                ->weight(FontWeight::SemiBold),
                            Grid::make(2)->schema([
                                TextEntry::make('categorie.name')
                                    ->label(__('general.category'))
                                    ->placeholder(__('general.not_set'))
                                    ->badge()
                                    ->url(fn (Model $record): ?string => $record->categorie
                                        ? route('filament.app.resources.order-articles.index', ['tableFilters' => ['category' => ['value' => $record->categorie->id]]])
                                        : null, true),
                                TextEntry::make('article_number')
                                    ->label(__('general.article_number'))
                                    ->extraAttributes(['style' => 'overflow-wrap: anywhere'])
                                    ->copyable()
                                    ->visible(fn (Model $record): bool => filled($record->article_number)),
                            ]),
                            TextEntry::make('price_net')
                                ->money(fn (Model $record) => match ($record->currency) {
                                    'EUR' => 'EUR',
                                    'USD' => 'USD',
                                    default => 'EUR',
                                })
                                ->label(__('general.price_net'))
                                ->size(TextSize::Large)
                                ->weight(FontWeight::Bold)
                                ->color('primary'),
                            TextEntry::make('price_gross')
                                ->money(fn (Model $record) => match ($record->currency) {
                                    'EUR' => 'EUR',
                                    'USD' => 'USD',
                                    default => 'EUR',
                                })
                                ->label(__('general.price_gross')),
                            TextEntry::make('tax_rate')
                                ->label(__('general.tax_rate'))
                                ->suffix('%'),
                            TextEntry::make('returning_deposit')
                                ->money(fn (Model $record) => match ($record->currency) {
                                    'EUR' => 'EUR',
                                    'USD' => 'USD',
                                    default => 'EUR',
                                })
                                ->label(__('general.returning_deposit'))
                                ->hint(__('general.additional').', '.__('general.gross'))
                                ->visible(fn ($record) => $record->returning_deposit > 0),
                            TextEntry::make('url')
                                ->url(function (Model $record) {
                                    return $record->url;
                                }, true)
                                ->label(__('general.url'))
                                ->formatStateUsing(fn (): string => __('general.url'))
                                ->icon('heroicon-o-arrow-top-right-on-square')
                                ->color('primary')
                                ->visible(function (Model $record) {
                                    return $record->url;
                                }),
                        ]),
                    ])
                    ->columnSpanFull(),
                Section::make(__('general.description'))
                    ->compact()
                    ->schema([
                        TextEntry::make('description')
                            ->hiddenLabel()
                            ->extraAttributes(['style' => 'overflow-wrap: anywhere'])
                            ->formatStateUsing(fn (string $state): HtmlString => new HtmlString(nl2br(e($state))))
                            ->prose(),
                    ])
                    ->visible(fn (Model $record): bool => filled($record->description))
                    ->columnSpanFull(),
                Section::make(__('general.comment'))
                    ->schema([
                        TextEntry::make('comment')
                            ->default(__('general.not_set'))
                            ->hiddenLabel()
                            ->extraAttributes(['style' => 'overflow-wrap: anywhere'])
                            ->formatStateUsing(fn (string $state): HtmlString => new HtmlString(nl2br(e($state))))
                            ->prose(),
                    ])
                    ->visible(function (Model $record) {
                        return $record->comment;
                    })
                    ->columnSpanFull(),
                Section::make(__('general.other_infos'))
                    ->collapsed()
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 2])->schema([
                            Group::make([
                                TextEntry::make('added_by')
                                    ->label(__('general.added_by'))
                                    ->state(fn (Model $record): ?string => $record->addedBy?->name)
                                    ->placeholder(__('general.not_set')),
                                TextEntry::make('edited_by')
                                    ->label(__('general.edited_by'))
                                    ->state(fn (Model $record): ?string => $record->editedBy?->name)
                                    ->placeholder(__('general.not_set')),
                            ]),
                            Group::make([
                                TextEntry::make('created_at')
                                    ->label(__('general.created_at'))
                                    ->dateTime(timezone: ApplicationTime::timezone()),
                                TextEntry::make('updated_at')
                                    ->label(__('general.updated_at'))
                                    ->dateTime(timezone: ApplicationTime::timezone()),
                            ]),
                        ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
