<?php

namespace App\Http\Controllers;

use App\Models\ClubMember;
use App\Services\ClubMemberFiles;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClubMemberFileController extends Controller
{
    public function __invoke(int $member, int $media): StreamedResponse
    {
        $record = ClubMember::withTrashed()->findOrFail($member);
        Gate::authorize('view', $record);
        $file = $record->media()->where('collection_name', ClubMember::FILE_COLLECTION)->findOrFail($media);
        $disk = Storage::disk($file->disk);
        abort_unless($disk->exists($file->getPathRelativeToRoot()), 404);

        return $disk->download($file->getPathRelativeToRoot(), ClubMemberFiles::originalName($file), [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
