<?php

namespace App\Jobs;

use App\Models\OrderArticle;
use App\Services\ArticleImageDownloader;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CacheOrderArticleImage implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public int $uniqueFor = 600;

    public function __construct(public int $articleId, public string $sourceUrl) {}

    public function uniqueId(): string
    {
        return $this->articleId.':'.hash('sha256', $this->sourceUrl);
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [30, 120];
    }

    /**
     * Execute the job.
     */
    public function handle(ArticleImageDownloader $downloader): void
    {
        $article = OrderArticle::find($this->articleId);
        if (! $article || $article->picture !== $this->sourceUrl) {
            return;
        }
        $image = $downloader->download($this->sourceUrl);
        DB::transaction(function () use ($image): void {
            $article = OrderArticle::lockForUpdate()->find($this->articleId);
            if (! $article || $article->picture !== $this->sourceUrl) {
                return;
            }
            $previousIds = $article->getMedia(OrderArticle::IMAGE_COLLECTION)->modelKeys();
            try {
                $media = $article->addMediaFromString($image['contents'])
                    ->usingFileName(Str::uuid().'.'.$image['extension'])
                    ->withCustomProperties(['source_url' => $this->sourceUrl])
                    ->toMediaCollection(OrderArticle::IMAGE_COLLECTION);
                if (! $media->hasGeneratedConversion('thumbnail')) {
                    throw new RuntimeException('Article image thumbnail could not be generated.');
                }
                foreach (['' => $media->disk, 'thumbnail' => $media->conversions_disk] as $conversion => $disk) {
                    if (! Storage::disk($disk)->setVisibility($media->getPathRelativeToRoot($conversion), 'private')) {
                        throw new RuntimeException('Article image could not be made private.');
                    }
                }
            } catch (Throwable $exception) {
                $article->media()->where('collection_name', OrderArticle::IMAGE_COLLECTION)
                    ->whereNotIn('id', $previousIds)->get()->each->delete();
                throw $exception;
            }
            $article->clearMediaCollectionExcept(OrderArticle::IMAGE_COLLECTION, [$media]);
        });
    }
}
