<?php

namespace App\Models;

use App\Services\ApplicationTime;
use Database\Factories\ClubMemberFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ClubMember extends Model implements HasMedia
{
    /** @use HasFactory<ClubMemberFactory> */
    use HasFactory, InteractsWithMedia, SoftDeletes;

    public const FILE_COLLECTION = 'club-member-files';

    public const TEXT_FIELDS = ['sona_name', 'first_name', 'last_name', 'street_address', 'postal_code', 'city', 'country', 'email', 'phone', 'comment'];

    public const DATE_FIELDS = ['birth_date', 'joined_at', 'left_at'];

    protected $fillable = [...self::TEXT_FIELDS, ...self::DATE_FIELDS];

    protected function casts(): array
    {
        return ['birth_date' => 'date', 'joined_at' => 'date', 'left_at' => 'date'];
    }

    public function membershipStatus(): string
    {
        $today = ApplicationTime::now()->toDateString();

        if ($this->joined_at->toDateString() > $today) {
            return 'planned';
        }

        return $this->left_at && $this->left_at->toDateString() <= $today ? 'departed' : 'active';
    }

    public function scopeMembershipStatus(Builder $query, string $status): Builder
    {
        $today = ApplicationTime::now()->toDateString();

        return match ($status) {
            'planned' => $query->whereDate('joined_at', '>', $today),
            'departed' => $query->whereDate('joined_at', '<=', $today)->whereDate('left_at', '<=', $today),
            'active' => $query->whereDate('joined_at', '<=', $today)->where(fn (Builder $query): Builder => $query->whereNull('left_at')->orWhereDate('left_at', '>', $today)),
            default => $query,
        };
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::FILE_COLLECTION);
    }
}
