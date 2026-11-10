<?php

namespace App\Filament\App\Resources\OrderArticles\Pages;

use App\Actions\HeaderOrderAction;
use App\Filament\App\Resources\OrderArticles\OrderArticleResource;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ViewOrderArticle extends ViewRecord
{
    protected static string $resource = OrderArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('add_to_wishlist')
                ->authorize(fn (): bool => Gate::allows('viewAny', Wishlist::class) && Gate::allows('update-Wishlist'))
                ->label(__('general.add_to_wishlist'))
                ->icon('heroicon-o-bookmark')
                ->form([
                    Select::make('wishlist_id')
                        ->label(__('general.wishlist'))
                        ->options(fn () => Gate::allows('update-Wishlist') ? Wishlist::where('user_id', Auth::id())->pluck('name', 'id') : [])
                        ->required()
                        ->createOptionAction(fn (Action $action): Action => $action->authorize(fn (): bool => Gate::allows('create', Wishlist::class)))
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
                            Gate::authorize('create', Wishlist::class);
                            $data['user_id'] = Auth::id();

                            return Wishlist::create($data)->id;
                        }),
                ])
                ->action(function (array $data, Model $record) {
                    $wishlist = Wishlist::findOrFail($data['wishlist_id']);
                    Gate::authorize('update', $wishlist);

                    WishlistItem::create([
                        'wishlist_id' => $data['wishlist_id'],
                        'order_article_id' => $record->id,
                    ]);
                    Notification::make()
                        ->title(__('general.added_to_wishlist'))
                        ->success()
                        ->send();
                }),
            EditAction::make()
                ->color(Color::Amber)
                ->icon('heroicon-o-pencil'),
            HeaderOrderAction::make(),
        ];
    }
}
