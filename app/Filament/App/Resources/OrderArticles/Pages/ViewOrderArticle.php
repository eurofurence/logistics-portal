<?php

namespace App\Filament\App\Resources\OrderArticles\Pages;

use Filament\Actions\EditAction;
use Filament\Actions;
use Filament\Actions\Action;
use App\Actions\HeaderOrderAction;
use Filament\Support\Colors\Color;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\App\Resources\OrderArticles\OrderArticleResource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Hidden;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class ViewOrderArticle extends ViewRecord
{
    protected static string $resource = OrderArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('add_to_wishlist')
                ->label(__('general.add_to_wishlist'))
                ->icon('heroicon-o-bookmark')
                ->form([
                    Select::make('wishlist_id')
                        ->label(__('general.wishlist'))
                        ->options(fn () => Wishlist::where('user_id', Auth::id())->pluck('name', 'id'))
                        ->required()
                        ->createOptionForm([
                            TextInput::make('name')
                                ->required()
                                ->label(__('general.name')),
                            Textarea::make('description')
                                ->label(__('general.description')),
                            Toggle::make('is_public')
                                ->label(__('general.is_public')),
                            Hidden::make('user_id')
                                ->default(fn () => Auth::id()),
                        ])
                        ->createOptionUsing(function (array $data) {
                            return Wishlist::create($data)->id;
                        }),
                ])
                ->action(function (array $data, Model $record) {
                    WishlistItem::create([
                        'wishlist_id' => $data['wishlist_id'],
                        'order_article_id' => $record->id,
                    ]);
                    Notification::make()
                        ->title(__('general.added_to_wishlist'))
                        ->success()
                        ->send();
                }),
            Action::make('back')
                ->label(__('general.back'))
                ->url(url()->previous())
                ->icon('heroicon-s-arrow-left')
                ->outlined(),
            EditAction::make()
                ->color(Color::Amber)
                ->icon('heroicon-o-pencil'),
            HeaderOrderAction::make()
        ];
    }
}
