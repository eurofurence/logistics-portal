<?php

namespace App\Filament\App\Resources\OrderArticles\Tables;

use App\Actions\TableOrderAction;
use App\Filament\App\Resources\OrderArticles\OrderArticleResource;
use App\Jobs\SyncDataToOrderArticleJob;
use App\Models\OrderArticle;
use App\Models\OrderCategory;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;

class OrderArticlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns(self::getColumns())
            ->filters(self::getFilters(), layout: FiltersLayout::AboveContent)
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->paginated([
                18,
                36,
                72,
                'all',
            ])
            ->recordActions(self::getRecordActions())
            ->toolbarActions(self::getToolbarActions());
    }

    public static function getColumns(): array
    {
        return [
            Split::make([
                ImageColumn::make('picture')
                    ->imageSize(100)
                    ->grow(false),
                Stack::make([
                    TextColumn::make('name')
                        ->weight(FontWeight::Bold)
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('description')
                        ->color('gray')
                        ->limit(100),
                    TextColumn::make('price_net')
                        ->label(__('general.price_net'))
                        ->sortable()
                        ->formatStateUsing(function (Model $record) {
                            $priceNet = number_format($record->price_net, 2);
                            $returningDeposit = number_format($record->returning_deposit, 2);
                            $currencySymbol = match ($record->currency) {
                                'EUR' => '€',
                                'USD' => '$',
                                default => '€',
                            };

                            if ($record->returning_deposit > 0) {
                                return "{$priceNet}{$currencySymbol} <span style='color: gray; font-size: 0.9em;'> + (".__('general.returning_deposit').": {$returningDeposit}{$currencySymbol})</span>";
                            }

                            return "{$priceNet}{$currencySymbol}";
                        })
                        ->html(),
                    TextColumn::make('article_number')
                        ->searchable()
                        ->html()
                        ->formatStateUsing(function () {
                            return '';
                        }),
                ]),
            ]),
        ];
    }

    public static function getFilters(): array
    {
        return [
            TrashedFilter::make()
                ->visible(fn (): bool => Gate::allows('restore', OrderArticle::class) || Gate::allows('forceDelete', OrderArticle::class) || Gate::allows('bulkForceDelete', OrderArticle::class) || Gate::allows('bulkRestore', OrderArticle::class)),
            SelectFilter::make('category')
                ->label(__('general.category'))
                ->options(OrderCategory::query()->pluck('name', 'id'))
                ->searchable(),
            SelectFilter::make('url')
                ->label(__('general.marketplace'))
                ->options([
                    'frog_store' => __('general.frog_store'),
                    'metro' => __('general.metro'),
                    'amazon' => __('general.amazon'),
                    'hornbach' => __('general.hornbach'),
                    'ikea' => __('general.ikea'),
                    'bauhaus' => __('general.bauhaus'),
                ])
                ->multiple()
                ->query(function (Builder $query, $data): Builder {
                    if (! empty($data['values'])) {
                        $query->where(function ($query) use ($data) {
                            foreach ($data['values'] as $value) {
                                if ($value === 'frog_store') {
                                    $query->orWhere('url', 'like', '%frog_store.%');
                                }

                                if ($value === 'metro') {
                                    $query->orWhere('url', 'like', '%metro.%');
                                }

                                if ($value === 'amazon') {
                                    $query->orWhere('url', 'like', '%amazon.%')
                                        ->orWhere('url', 'like', '%amzn.%')
                                        ->orWhere('url', 'like', '%amzn.eu%');
                                }

                                if ($value === 'hornbach') {
                                    $query->orWhere('url', 'like', '%hornbach.%');
                                }

                                if ($value === 'ikea') {
                                    $query->orWhere('url', 'like', '%ikea.%');
                                }

                                if ($value === 'bauhaus') {
                                    $query->orWhere('url', 'like', '%bauhaus.%');
                                }
                            }
                        });
                    }

                    return $query;
                }),
        ];
    }

    public static function getRecordActions(): array
    {
        return [
            TableOrderAction::make()
                ->button()
                ->size(Size::ExtraSmall),
            ActionGroup::make([
                EditAction::make(),
                DeleteAction::make()
                    ->modalHeading(function ($record): string {
                        return __('general.delete').': '.$record->name;
                    }),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
                ->button()
                ->size(Size::ExtraSmall)
                ->color(Color::Indigo)
                ->outlined(),
            Action::make('article_note')
                ->label(__('general.note'))
                ->icon('heroicon-o-shield-exclamation')
                ->color(Color::Yellow)
                ->size(Size::ExtraSmall)
                ->visible(function (Model $record): bool {
                    return ! empty(OrderArticleResource::getOrderArticleNotes($record));
                })
                ->schema(function (Model $record) {
                    return [
                        ViewField::make('note_list')
                            ->view('components.form-list')
                            ->viewData([
                                'notes' => OrderArticleResource::getOrderArticleNotes($record),
                            ]),
                    ];
                })
                ->modalIcon('heroicon-o-shield-exclamation')
                ->modalWidth(Width::ExtraLarge)
                ->modalSubmitAction(false)
                ->modalCancelAction(false),
        ];
    }

    public static function getToolbarActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make()
                    ->visible(Gate::check('bulkDelete', OrderArticle::class)),
                RestoreBulkAction::make()
                    ->visible(Gate::check('bulkRestore', OrderArticle::class)),
                ForceDeleteBulkAction::make()
                    ->visible(Gate::check('bulkForceDelete', OrderArticle::class)),
                BulkAction::make('bulk_calc_gross_price')
                    ->label(__('general.recalculate_gross_price'))
                    ->icon('heroicon-o-arrow-path-rounded-square')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        foreach ($records as $article) {
                            if ($article->trashed()) {
                                continue;
                            }

                            $priceGross = $article->price_net * (1 + $article->tax_rate / 100);

                            $article->update([
                                'price_gross' => $priceGross,
                            ]);
                        }

                        Notification::make()
                            ->body(__('general.successfully_recalculated'))
                            ->success()
                            ->icon('heroicon-o-check')
                            ->iconColor('success')
                            ->send();
                    })
                    ->visible(Auth::user()->can('can-use-article-directory-special-functions')),
                BulkAction::make('SyncArticleDataToExternalSource')
                    ->color('info')
                    ->requiresConfirmation()
                    ->label(__('general.get_amazon_data'))
                    ->icon('heroicon-o-arrow-path-rounded-square')
                    ->visible(Auth::user()->can('can-use-article-directory-special-functions'))
                    ->schema([
                        TextEntry::make(__('general.hint'))
                            ->state(new HtmlString('<b><label style="color: orange">'.__('general.selected_fields_will_be_overwritten').'</label></b>'))
                            ->extraAttributes(['class' => 'text-red-500'])
                            ->columnSpanFull(),
                        Select::make('fields')
                            ->label(__('general.select_fields'))
                            ->options([
                                'name' => __('general.name'),
                                // 'description' => __('general.description'),
                                'price_gross' => __('general.price_gross'),
                                'picture' => __('general.picture'),
                                'url' => __('general.url'),
                                'article_number' => __('general.article_number'),
                            ])
                            ->default([
                                'price_gross',
                                'url',
                                'article_number',
                            ])
                            ->multiple()
                            ->reactive()
                            ->required(),
                        Toggle::make('select_all')
                            ->label(__('general.select_all'))
                            ->reactive()
                            ->afterStateUpdated(function (Set $set, $state) {
                                if ($state) {
                                    $set('fields', [
                                        'name',
                                        // 'description',
                                        'price_gross',
                                        'picture',
                                        'url',
                                        'article_number',
                                    ]);
                                } else {
                                    $set('fields', []);
                                }
                            })->default(false),
                    ])
                    ->action(function (Collection $records, array $data) {
                        $jobs = [];

                        foreach ($records as $article) {
                            if ($article->trashed()) {
                                continue;
                            }

                            // Add the job to the array
                            $jobs[] = new SyncDataToOrderArticleJob($article, Auth::user()->id, $data['fields']);
                        }

                        // Now create the batch with the collected jobs
                        Bus::batch($jobs)
                            ->allowFailures()
                            ->dispatch();

                        Notification::make()
                            ->body(__('general.job_started'))
                            ->success()
                            ->icon('heroicon-o-check')
                            ->iconColor('success')
                            ->send();
                    }),
                BulkAction::make('bulkChangeDeadline')
                    ->color(Color::Indigo)
                    ->requiresConfirmation()
                    ->label(__('general.set_deadline'))
                    ->icon('heroicon-o-calendar-days')
                    ->visible(Gate::allows('bulkEditDeadline'))
                    ->schema([
                        DateTimePicker::make('deadline')
                            ->label(__('general.date_and_time'))
                            ->timezone('Europe/Berlin')
                            ->seconds(false)
                            ->nullable(),
                    ])
                    ->action(function (Collection $records, array $data) {
                        foreach ($records as $article) {
                            if ($article->trashed()) {
                                continue;
                            }

                            $article->update([
                                'deadline' => $data['deadline'],
                            ]);
                        }

                        Notification::make()
                            ->body(__('general.deadline_set'))
                            ->success()
                            ->icon('heroicon-o-check')
                            ->iconColor('success')
                            ->send();
                    }),
            ]),
        ];
    }
}
