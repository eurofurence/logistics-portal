<?php

namespace App\Http\Controllers;

use App\Models\OrderArticle;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderArticleImageController extends Controller
{
    public function current(int $article, string $conversion = 'original'): StreamedResponse
    {
        $record = OrderArticle::withTrashed()->findOrFail($article);
        Gate::authorize('view', $record);
        $image = $record->media()->where('collection_name', OrderArticle::IMAGE_COLLECTION)->firstOrFail();

        return $this->imageResponse($image, $conversion);
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(int $article, int $media, string $conversion = 'original'): StreamedResponse
    {
        $record = OrderArticle::withTrashed()->findOrFail($article);
        Gate::authorize('view', $record);
        $image = $record->media()->where('collection_name', OrderArticle::IMAGE_COLLECTION)->findOrFail($media);

        return $this->imageResponse($image, $conversion);
    }

    private function imageResponse(Media $image, string $conversion): StreamedResponse
    {
        $thumbnail = $conversion === 'thumbnail' && $image->hasGeneratedConversion('thumbnail');
        $path = $image->getPathRelativeToRoot($thumbnail ? 'thumbnail' : '');
        $disk = Storage::disk($thumbnail ? $image->conversions_disk : $image->disk);
        if ($thumbnail && ! $disk->exists($path)) {
            $thumbnail = false;
            $path = $image->getPathRelativeToRoot();
            $disk = Storage::disk($image->disk);
        }
        abort_unless($disk->exists($path), 404);

        return $disk->response($path, null, [
            'Content-Type' => $thumbnail ? 'image/webp' : $image->mime_type,
            'Cache-Control' => 'private, no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
