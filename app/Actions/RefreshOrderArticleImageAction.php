<?php

namespace App\Actions;

use App\Jobs\CacheOrderArticleImage;
use App\Models\OrderArticle;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Gate;

class RefreshOrderArticleImageAction
{
    public static function make(): Action
    {
        return Action::make('refreshArticleImage')
            ->label(__('article_images.refresh'))
            ->icon('heroicon-o-arrow-path')
            ->visible(fn (OrderArticle $record): bool => ! $record->trashed() && filled($record->picture))
            ->authorize(fn (OrderArticle $record): bool => Gate::allows('refreshImage', $record))
            ->action(function (OrderArticle $record): void {
                $article = OrderArticle::findOrFail($record->id);
                Gate::authorize('refreshImage', $article);
                CacheOrderArticleImage::dispatch($article->id, $article->picture)->afterCommit();
                Notification::make()->title(__('article_images.queued'))->success()->send();
            });
    }
}
