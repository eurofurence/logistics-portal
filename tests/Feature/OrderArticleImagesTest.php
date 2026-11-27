<?php

use App\Filament\App\Resources\OrderArticles\Pages\ListOrderArticles;
use App\Filament\App\Resources\OrderArticles\Pages\ViewOrderArticle;
use App\Jobs\CacheOrderArticleImage;
use App\Models\OrderArticle;
use App\Models\Permission;
use App\Models\User;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use App\Services\ArticleImageDownloader;
use App\Settings\LoginSettings;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;

function articleImageUser(): User
{
    $user = User::factory()->create();
    foreach (['view-OrderArticle', 'view-any-OrderArticle'] as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    return $user;
}

function articleImageContents(): string
{
    return UploadedFile::fake()->image('article.png', 800, 600)->get();
}

test('bulk image refresh queues selected articles with pictures only', function () {
    Filament::setCurrentPanel(Filament::getPanel('app'));
    $user = articleImageUser();
    $user->givePermissionTo(Permission::findOrCreate('refresh-image-OrderArticle', 'web'));
    $this->actingAs($user);
    $articles = OrderArticle::factory()->count(2)->create();
    foreach ($articles as $article) {
        $article->updateQuietly(['picture' => 'https://example.com/article-'.$article->id.'.png']);
    }
    $withoutPicture = OrderArticle::factory()->create(['picture' => null]);
    $unselected = OrderArticle::factory()->create();
    $unselected->updateQuietly(['picture' => 'https://example.com/unselected.png']);
    Queue::fake([CacheOrderArticleImage::class]);

    Livewire::test(ListOrderArticles::class)
        ->callTableBulkAction('refreshArticleImages', $articles->push($withoutPicture))
        ->assertNotified();

    Queue::assertPushed(CacheOrderArticleImage::class, 2);
    foreach ($articles->except($withoutPicture->id) as $article) {
        Queue::assertPushed(CacheOrderArticleImage::class, fn (CacheOrderArticleImage $job): bool => $job->articleId === $article->id && $job->sourceUrl === $article->picture);
    }
});

test('bulk image refresh is hidden without its dedicated permission', function () {
    Filament::setCurrentPanel(Filament::getPanel('app'));
    $this->actingAs(articleImageUser());

    Livewire::test(ListOrderArticles::class)->assertTableBulkActionHidden('refreshArticleImages');
});

test('shows an important article note without a lock or deadline', function () {
    Filament::setCurrentPanel(Filament::getPanel('app'));
    $this->actingAs(articleImageUser());
    $article = OrderArticle::factory()->create([
        'locked' => false,
        'deadline' => null,
        'important_note' => 'Keep refrigerated after delivery',
    ]);

    Livewire::test(ViewOrderArticle::class, ['record' => $article->id])
        ->assertSuccessful()
        ->assertSee('Keep refrigerated after delivery');
});

test('shows the deposit in the article currency', function () {
    Filament::setCurrentPanel(Filament::getPanel('app'));
    $this->actingAs(articleImageUser());
    app()->setLocale('en');
    $article = OrderArticle::factory()->create([
        'currency' => 'USD',
        'returning_deposit' => 2.50,
    ]);

    Livewire::test(ViewOrderArticle::class, ['record' => $article->id])
        ->assertSuccessful()
        ->assertSee('$2.50')
        ->assertDontSee('€2.50');
});

test('stores the original and a smaller thumbnail and serves both with authorization', function () {
    app(LoginSettings::class)->whitelist_active = false;
    Queue::fake([CacheOrderArticleImage::class]);
    $storagePath = storage_path();
    app()->useStoragePath(sys_get_temp_dir().'/article-image-tests-'.Str::uuid());
    try {
        Storage::fake('local');
    } finally {
        app()->useStoragePath($storagePath);
    }
    config(['media-library.disk_name' => 'local']);
    Http::preventStrayRequests();
    $contents = articleImageContents();
    $url = 'https://93.184.216.34/article.png';
    Http::fake([$url => Http::response($contents)]);
    $this->actingAs(articleImageUser());
    $article = OrderArticle::factory()->create(['picture' => $url]);

    (new CacheOrderArticleImage($article->id, $url))->handle(app(ArticleImageDownloader::class));

    $article->refresh();
    $media = $article->getFirstMedia(OrderArticle::IMAGE_COLLECTION);
    expect($article->picture)->toBe($url)
        ->and($media->hasGeneratedConversion('thumbnail'))->toBeTrue()
        ->and($article->picture_url)->not->toBe($url);
    Storage::disk('local')->assertExists($media->getPathRelativeToRoot());
    expect(Storage::disk('local')->getVisibility($media->getPathRelativeToRoot()))->toBe('private');
    $this->get($article->picture_url)->assertSuccessful()->assertHeader('Content-Type', 'image/png')->assertStreamedContent($contents);
    $this->get($article->picture_thumbnail_url)->assertSuccessful()->assertHeader('Content-Type', 'image/webp');
    $previousThumbnailUrl = $article->picture_thumbnail_url;
    (new CacheOrderArticleImage($article->id, $url))->handle(app(ArticleImageDownloader::class));
    $article->refresh();
    $this->get($previousThumbnailUrl)->assertSuccessful()->assertHeader('Content-Type', 'image/webp');
    expect($article->media()->count())->toBe(1)
        ->and($article->getFirstMedia(OrderArticle::IMAGE_COLLECTION)->id)->not->toBe($media->id);
    Storage::disk('local')->assertMissing($media->getPathRelativeToRoot());
    $article->delete();
    $this->get($article->picture_url)->assertSuccessful();

    $other = OrderArticle::factory()->create();
    $this->getJson(route('order-articles.images', ['article' => $other->id, 'media' => $article->getFirstMedia(OrderArticle::IMAGE_COLLECTION)->id]))->assertNotFound();
    $this->actingAs(User::factory()->create())->getJson($article->picture_url)->assertForbidden();
    auth()->logout();
    $this->getJson($article->picture_url)->assertUnauthorized();
    Http::assertSentCount(2);
    Queue::assertPushed(CacheOrderArticleImage::class);
});

test('serves the original from the media disk when a generated thumbnail is missing', function () {
    app(LoginSettings::class)->whitelist_active = false;
    Queue::fake([CacheOrderArticleImage::class]);
    Storage::fake('s3');
    Storage::fake('local');
    config(['media-library.disk_name' => 's3']);
    $this->actingAs(articleImageUser());
    $article = OrderArticle::factory()->create(['picture' => 'https://example.com/image.png']);
    $contents = articleImageContents();
    $media = $article->addMediaFromString($contents)->usingFileName('article.png')
        ->storingConversionsOnDisk('local')->toMediaCollection(OrderArticle::IMAGE_COLLECTION);
    Storage::disk('local')->delete($media->getPathRelativeToRoot('thumbnail'));

    $this->get($article->picture_thumbnail_url)->assertSuccessful()
        ->assertHeader('Content-Type', 'image/png')->assertStreamedContent($contents);

    Storage::disk('s3')->assertExists($media->getPathRelativeToRoot());
    Storage::disk('local')->assertMissing($media->getPathRelativeToRoot('thumbnail'));
    Queue::assertPushed(CacheOrderArticleImage::class);
});

test('keeps the previous copy when refresh fails', function (int $status, string $body) {
    Queue::fake([CacheOrderArticleImage::class]);
    Storage::fake('local');
    config(['media-library.disk_name' => 'local']);
    Http::preventStrayRequests();
    $url = 'https://93.184.216.34/article.png';
    $this->actingAs(articleImageUser());
    $article = OrderArticle::factory()->create(['picture' => $url]);
    $media = $article->addMediaFromString(articleImageContents())->usingFileName('old.png')->toMediaCollection(OrderArticle::IMAGE_COLLECTION);
    Http::fake([$url => Http::response($body, $status)]);

    expect(fn () => (new CacheOrderArticleImage($article->id, $url))->handle(app(ArticleImageDownloader::class)))
        ->toThrow(RuntimeException::class);
    expect($article->fresh()->getFirstMedia(OrderArticle::IMAGE_COLLECTION)->id)->toBe($media->id);
    Storage::disk('local')->assertExists($media->getPathRelativeToRoot());
    Http::assertSentCount(1);
    Queue::assertPushed(CacheOrderArticleImage::class);
})->with([
    'unavailable source' => [503, 'Unavailable'],
    'invalid contents' => [200, '<html>Not an image</html>'],
    'oversized body' => [200, str_repeat('a', 10 * 1024 * 1024 + 1)],
]);

test('rejects private addresses and unsafe URL schemes before requesting them', function (string $url) {
    Http::preventStrayRequests();
    expect(fn () => app(ArticleImageDownloader::class)->download($url))->toThrow(RuntimeException::class);
    Http::assertNothingSent();
})->with(['http://127.0.0.1/image', 'http://10.0.0.1/image', 'http://169.254.169.254/image', 'http://[::1]/image', 'http://[::ffff:127.0.0.1]/image', 'file:///etc/passwd', 'https://user:pass@example.com/image', 'https://1.1.1.1:8080/image']);

test('revalidates redirect addresses', function () {
    Http::preventStrayRequests();
    $url = 'https://93.184.216.34/article.png';
    Http::fake([$url => Http::response('', 302, ['Location' => 'http://127.0.0.1/private'])]);
    expect(fn () => app(ArticleImageDownloader::class)->download($url))->toThrow(RuntimeException::class);
    Http::assertSentCount(1);
});

test('ignores stale queued URLs without downloading', function () {
    Http::preventStrayRequests();
    $this->actingAs(articleImageUser());
    $article = OrderArticle::factory()->create();
    (new CacheOrderArticleImage($article->id, 'https://93.184.216.34/old.png'))->handle(app(ArticleImageDownloader::class));
    Http::assertNothingSent();
});

test('queues image downloads only on creation and source changes', function () {
    Queue::fake([CacheOrderArticleImage::class]);
    $this->actingAs(articleImageUser());
    $article = OrderArticle::factory()->create(['picture' => 'https://93.184.216.34/first.png']);
    $article->update(['name' => 'Changed name']);
    $article->update(['picture' => 'https://93.184.216.34/second.png']);
    $article->update(['picture' => null]);
    Queue::assertPushed(CacheOrderArticleImage::class, 2);
    Queue::assertPushed(CacheOrderArticleImage::class, fn ($job): bool => $job->sourceUrl === 'https://93.184.216.34/second.png' && $job->afterCommit);
});

test('requires a separate permission for the manual image action', function () {
    Queue::fake([CacheOrderArticleImage::class]);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    app(LoginSettings::class)->whitelist_active = false;
    $user = articleImageUser();
    $user->givePermissionTo(Permission::findOrCreate('update-OrderArticle', 'web'));
    $this->actingAs($user);
    $article = OrderArticle::factory()->create();
    $article->updateQuietly(['picture' => 'https://93.184.216.34/article.png']);
    Livewire::test(ViewOrderArticle::class, ['record' => $article->id])->assertActionHidden('refreshArticleImage');
    expect(Gate::allows('refreshImage', $article))->toBeFalse();

    $user->revokePermissionTo('update-OrderArticle');
    $user->givePermissionTo(Permission::findOrCreate('refresh-image-OrderArticle', 'web'));
    Livewire::test(ViewOrderArticle::class, ['record' => $article->id])
        ->assertActionVisible('refreshArticleImage')->callAction('refreshArticleImage')->assertNotified();
    Queue::assertPushed(CacheOrderArticleImage::class, 1);
    $article->delete();
    expect(Gate::allows('refreshImage', $article))->toBeFalse();
});

test('backfills missing image copies but skips a current cached image', function () {
    Queue::fake([CacheOrderArticleImage::class]);
    Storage::fake('local');
    config(['media-library.disk_name' => 'local']);
    $this->actingAs(articleImageUser());
    $url = 'https://93.184.216.34/article.png';
    $cached = OrderArticle::factory()->create();
    $cached->updateQuietly(['picture' => $url]);
    $cached->addMediaFromString(articleImageContents())->usingFileName('cached.png')
        ->withCustomProperties(['source_url' => $url])->toMediaCollection(OrderArticle::IMAGE_COLLECTION);
    $missing = OrderArticle::factory()->create();
    $missing->updateQuietly(['picture' => $url]);
    $this->artisan('articles:cache-images')->expectsOutput('Queued 1 article image downloads.')->assertSuccessful();
    Queue::assertPushed(CacheOrderArticleImage::class, fn ($job): bool => $job->articleId === $missing->id);
    Queue::assertPushed(CacheOrderArticleImage::class, 1);
});

test('uses the cached thumbnail in the directory, detail and wishlist', function () {
    Filament::setCurrentPanel(Filament::getPanel('app'));
    app(LoginSettings::class)->whitelist_active = false;
    Storage::fake('local');
    config(['media-library.disk_name' => 'local']);
    $user = articleImageUser();
    $this->actingAs($user);
    $article = OrderArticle::factory()->create();
    $source = 'https://93.184.216.34/article.png';
    $article->updateQuietly(['picture' => $source]);
    $article->addMediaFromString(articleImageContents())->usingFileName('cached.png')->toMediaCollection(OrderArticle::IMAGE_COLLECTION);
    $article->refresh();

    Livewire::test(ListOrderArticles::class)->assertSee($article->picture_thumbnail_url)->assertDontSee($source);
    Livewire::test(ViewOrderArticle::class, ['record' => $article->id])->assertSee($article->picture_thumbnail_url)->assertDontSee($source);
    $wishlist = Wishlist::factory()->create(['user_id' => $user->id, 'name' => 'Cached images']);
    $item = WishlistItem::factory()->create(['wishlist_id' => $wishlist->id, 'order_article_id' => $article->id]);
    $html = view('filament.app.wishlists.card', ['wishlist' => $wishlist, 'item' => $item, 'canEdit' => false])->render();
    expect($html)->toContain($article->picture_thumbnail_url)->not->toContain($source);
});

test('keeps the previous copy when thumbnail generation fails', function () {
    Storage::fake('local');
    config(['media-library.disk_name' => 'local']);
    Http::preventStrayRequests();
    $this->actingAs(articleImageUser());
    $article = OrderArticle::factory()->create();
    $url = 'https://93.184.216.34/article.png';
    $article->updateQuietly(['picture' => $url]);
    $contents = articleImageContents();
    $media = $article->addMediaFromString($contents)->usingFileName('old.png')->toMediaCollection(OrderArticle::IMAGE_COLLECTION);
    Http::fake([$url => Http::response(substr($contents, 0, 33))]);

    expect(fn () => (new CacheOrderArticleImage($article->id, $url))->handle(app(ArticleImageDownloader::class)))->toThrow(Exception::class);
    expect($article->fresh()->media()->count())->toBe(1);
    Storage::disk('local')->assertExists($media->getPathRelativeToRoot());
    Http::assertSentCount(1);
});
