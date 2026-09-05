<?php

namespace App\Filament\App\Resources\OrderArticles\Schemas;

use App\Filament\App\Resources\OrderArticles\OrderArticleResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Njxqlus\Filament\Components\Infolists\LightboxImageEntry;

class OrderArticleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
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
                    ->description(__('general.note'))
                    ->icon('heroicon-m-shield-exclamation')
                    ->iconColor('warning')
                    ->visible(function (Model $record) {
                        return $record->locked || ! empty($record->deadline);
                    }),
                Section::make(__('general.informations'))
                    ->columns([
                        'sm' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ])
                    ->schema([
                        LightboxImageEntry::make('picture')
                            ->href(
                                function (Model $record): string {
                                    return $record->picture;
                                }
                            )
                            ->extraImgAttributes([
                                'style' => 'object-fit: contain',
                            ], true)
                            ->label(__('general.picture'))
                            ->visible(function (Model $record) {
                                return $record->picture;
                            })
                            ->imageSize(255)
                            ->slideHeight('100%')
                            ->slideWidth('100%'),
                        Group::make([
                            TextEntry::make('name')
                                ->label(__('general.name')),
                            TextEntry::make('price_net')
                                ->money(fn (Model $record) => match ($record->currency) {
                                    'EUR' => 'EUR',
                                    'USD' => 'USD',
                                    default => 'EUR',
                                })
                                ->label(__('general.price_net')),
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
                            TextEntry::make('article_number')
                                ->label(__('general.article_number'))
                                ->default(__('general.not_set'))
                                ->visible(function (Model $record) {
                                    return $record->article_number;
                                }),
                        ]),
                        Group::make([
                            TextEntry::make('returning_deposit')
                                ->money(fn (Model $record) => match ($record->returning_deposit) {
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
                                ->default(__('general.not_set'))
                                ->limit(45)
                                ->visible(function (Model $record) {
                                    return $record->url;
                                }),
                            TextEntry::make('categorie.name')
                                ->label(__('general.category'))
                                ->default(__('general.not_set'))
                                ->url(function (Model $record) {
                                    if (! empty($record->categorie)) {
                                        return route('filament.app.resources.order-articles.index').'?tableFilters[category][value]='.$record->categorie->id;
                                    }
                                }, true),
                            TextEntry::make('description')
                                ->label(__('general.description'))
                                ->visible(function (Model $record) {
                                    return $record->description;
                                }),
                        ]),
                    ])
                    ->columnSpanFull(),
                Section::make(__('general.comment'))
                    ->schema([
                        TextEntry::make('comment')
                            ->default(__('general.not_set'))
                            ->label(''),
                    ])
                    ->visible(function (Model $record) {
                        return $record->comment;
                    }),
                Section::make(__('general.other_infos'))
                    ->schema([
                        Flex::make([
                            Group::make([
                                TextEntry::make('added_by')
                                    ->label(__('general.added_by'))
                                    ->state(fn (Model $record) => $record->addedBy->name),
                                TextEntry::make('edited_by')
                                    ->label(__('general.edited_by'))
                                    ->state(fn (Model $record) => $record->editedBy->name),
                            ]),
                            Group::make([
                                TextEntry::make('created_at')
                                    ->label(__('general.created_at'))
                                    ->dateTime(timezone: 'Europe/Berlin'),
                                TextEntry::make('updated_at')
                                    ->label(__('general.updated_at'))
                                    ->dateTime(timezone: 'Europe/Berlin'),
                            ]),
                        ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
