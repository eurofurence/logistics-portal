<?php

namespace App\Console\Commands;

use App\Jobs\CacheOrderArticleImage;
use App\Models\OrderArticle;
use Illuminate\Console\Command;

class CacheOrderArticleImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'articles:cache-images {--refresh : Refresh images that are already cached}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Queue persistent copies of external article images';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $queued = 0;
        OrderArticle::with('media')->whereNotNull('picture')->where('picture', '!=', '')
            ->chunkById(100, function ($articles) use (&$queued): void {
                foreach ($articles as $article) {
                    $media = $article->getFirstMedia(OrderArticle::IMAGE_COLLECTION);
                    if (! $this->option('refresh') && $media?->getCustomProperty('source_url') === $article->picture) {
                        continue;
                    }
                    CacheOrderArticleImage::dispatch($article->id, $article->picture)->afterCommit();
                    $queued++;
                }
            });
        $this->info("Queued {$queued} article image downloads.");

        return self::SUCCESS;
    }
}
