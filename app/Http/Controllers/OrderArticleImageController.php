<?php

namespace App\Http\Controllers;

use App\Models\OrderArticle;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderArticleImageController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(int $article, int $media, string $conversion = 'original'): StreamedResponse
    {
        $record = OrderArticle::withTrashed()->findOrFail($article);
        Gate::authorize('view', $record);
        $image = $record->media()->where('collection_name', OrderArticle::IMAGE_COLLECTION)->findOrFail($media);
        $thumbnail = $conversion === 'thumbnail' && $image->hasGeneratedConversion('thumbnail');
        $path = $image->getPathRelativeToRoot($thumbnail ? 'thumbnail' : '');
        $disk = Storage::disk($thumbnail ? $image->conversions_disk : $image->disk);
        abort_unless($disk->exists($path), 404);

        return $disk->response($path, null, [
            'Content-Type' => $thumbnail ? 'image/webp' : $image->mime_type,
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
