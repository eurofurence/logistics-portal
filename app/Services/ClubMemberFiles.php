<?php

namespace App\Services;

use App\Models\ClubMember;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class ClubMemberFiles
{
    public static function originalName(Media $file): string
    {
        return Crypt::decryptString($file->getCustomProperty('encrypted_original_name'));
    }

    public const MIME_TYPES = [
        'application/pdf', 'image/jpeg', 'image/png',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    /** @param array<int, UploadedFile> $uploads */
    public function validateUploads(array $uploads): void
    {
        Validator::make(['uploads' => $uploads], [
            'uploads' => ['array', 'max:5'],
            'uploads.*' => ['file', 'mimes:pdf,jpg,jpeg,png,docx,xlsx', 'extensions:pdf,jpg,jpeg,png,docx,xlsx', 'max:10000'],
        ])->validate();
    }

    /** @param array<int, int|string> $ids
     * @return Collection<int, Media>
     */
    public function attachments(ClubMember $member, array $ids): Collection
    {
        $ids = array_unique($ids);
        $files = $member->media()->where('collection_name', ClubMember::FILE_COLLECTION)->whereIn('id', $ids)->get();

        if ($files->count() !== count($ids)) {
            throw ValidationException::withMessages(['attachments' => __('members.invalid_attachment')]);
        }

        foreach ($files as $file) {
            if (! Storage::disk($file->disk)->exists($file->getPathRelativeToRoot())) {
                throw ValidationException::withMessages(['attachments' => __('members.invalid_attachment')]);
            }
        }

        return $files;
    }

    /** @param array<int, UploadedFile> $uploads
     * @param  array<int, int|string>  $attachmentIds
     * @return Collection<int, Media>
     */
    public function prepareEmail(ClubMember $member, array $uploads, array $attachmentIds): Collection
    {
        return DB::transaction(function () use ($member, $uploads, $attachmentIds): Collection {
            $member = ClubMember::query()->lockForUpdate()->findOrFail($member->id);
            Gate::authorize('sendEmail', $member);
            if ($uploads !== []) {
                Gate::authorize('manageFiles', $member);
            }
            $this->validateUploads($uploads);
            $files = $this->attachments($member, $attachmentIds);
            if ($files->sum('size') + collect($uploads)->sum(fn (UploadedFile $file): int => $file->getSize()) > 10 * 1024 * 1024) {
                throw ValidationException::withMessages(['attachments' => __('members.attachments_too_large')]);
            }
            $this->checkCount($member->media()->where('collection_name', ClubMember::FILE_COLLECTION)->count() + count($uploads));

            return $files->concat($this->persist($member, $uploads));
        });
    }

    /** @param array<int, string|UploadedFile> $state */
    public function sync(ClubMember $member, array $state): void
    {
        DB::transaction(function () use ($member, $state): void {
            $member = ClubMember::query()->lockForUpdate()->findOrFail($member->id);
            Gate::authorize('update', $member);
            Gate::authorize('manageFiles', $member);
            $uploads = array_values(array_filter($state, fn (mixed $file): bool => $file instanceof UploadedFile));
            $uuids = array_values(array_filter($state, fn (mixed $file): bool => is_string($file)));
            $existing = $member->media()->where('collection_name', ClubMember::FILE_COLLECTION)->get();
            if (count($state) !== count($uploads) + count($uuids) || count(array_unique($uuids)) !== count($uuids) || count(array_diff($uuids, $existing->pluck('uuid')->all())) > 0) {
                throw ValidationException::withMessages(['data.files' => __('members.invalid_attachment')]);
            }
            $this->validateUploads($uploads);
            $this->checkCount(count($uuids) + count($uploads));
            $this->persist($member, $uploads);
            foreach ($existing as $file) {
                if (! in_array($file->uuid, $uuids, true)) {
                    $file->delete();
                }
            }
        });
    }

    private function checkCount(int $count): void
    {
        if ($count > 5) {
            throw ValidationException::withMessages(['uploads' => __('members.too_many_files')]);
        }
    }

    /** @param array<int, UploadedFile> $uploads
     * @return Collection<int, Media>
     */
    private function persist(ClubMember $member, array $uploads): Collection
    {
        $stored = collect();
        try {
            foreach ($uploads as $upload) {
                $originalName = basename($upload->getClientOriginalName());
                $media = $member->addMediaFromString($upload instanceof TemporaryUploadedFile ? $upload->get() : $upload->getContent())
                    ->usingName('Member attachment')
                    ->usingFileName(Str::uuid().'.'.strtolower($upload->getClientOriginalExtension()))
                    ->withCustomProperties(['encrypted_original_name' => Crypt::encryptString($originalName)])
                    ->toMediaCollection(ClubMember::FILE_COLLECTION, config('filesystems.default'));
                $stored->push($media);
                if (! Storage::disk($media->disk)->setVisibility($media->getPathRelativeToRoot(), 'private')) {
                    throw new RuntimeException('Unable to set private visibility for member file.');
                }
            }
        } catch (Throwable $exception) {
            foreach ($stored as $media) {
                $media->delete();
            }
            throw $exception;
        }

        return $stored;
    }
}
