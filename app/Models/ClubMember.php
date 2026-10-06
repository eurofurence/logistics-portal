<?php

namespace App\Models;

use App\Services\ApplicationTime;
use App\Services\EncryptedMemberDate;
use Closure;
use Database\Factories\ClubMemberFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
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
        return [
            ...array_fill_keys(self::TEXT_FIELDS, 'encrypted'),
            ...array_fill_keys(self::DATE_FIELDS, EncryptedMemberDate::class),
            'personal_data_encrypted' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ClubMember $member): void {
            $member->personal_data_encrypted = true;
        });
    }

    /** @param Closure(ClubMember): bool $matches */
    public function scopeMatchingPersonalData(Builder $query, Closure $matches): Builder
    {
        $ids = (clone $query)->withTrashed()->reorder()->lazyById()
            ->filter($matches)->map(fn (ClubMember $member): int => $member->id)->all();

        return $query->whereKey($ids);
    }

    /** @param array<int, string> $fields */
    public function scopeSearchPersonalData(Builder $query, array $fields, string $search): Builder
    {
        return $query->matchingPersonalData(function (ClubMember $member) use ($fields, $search): bool {
            foreach ($fields as $field) {
                if (Str::contains($member->{$field} ?? '', $search, ignoreCase: true)) {
                    return true;
                }
            }

            return false;
        });
    }

    /** @param array<int, string> $fields */
    public function scopeOrderByPersonalData(Builder $query, array $fields, string $direction = 'asc'): Builder
    {
        $ids = (clone $query)->reorder()->get()->sort(function (ClubMember $first, ClubMember $second) use ($fields, $direction): int {
            foreach ($fields as $field) {
                $firstValue = in_array($field, self::DATE_FIELDS, true) ? $first->{$field}?->toDateString() : $first->{$field};
                $secondValue = in_array($field, self::DATE_FIELDS, true) ? $second->{$field}?->toDateString() : $second->{$field};
                $comparison = strnatcasecmp($firstValue ?? '', $secondValue ?? '');
                if ($comparison !== 0) {
                    return $direction === 'desc' ? -$comparison : $comparison;
                }
            }

            return $first->id <=> $second->id;
        })->values()->modelKeys();

        $query->reorder();
        if ($ids === []) {
            return $query->orderBy('id');
        }

        $cases = implode(' ', array_fill(0, count($ids), 'WHEN ? THEN ?'));
        $bindings = [];
        foreach ($ids as $position => $id) {
            $bindings[] = $id;
            $bindings[] = $position;
        }

        return $query->orderByRaw('CASE club_members.id '.$cases.' END', $bindings);
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
        return in_array($status, ['planned', 'departed', 'active'], true)
            ? $query->matchingPersonalData(fn (ClubMember $member): bool => $member->membershipStatus() === $status)
            : $query;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::FILE_COLLECTION);
    }
}
