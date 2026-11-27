<?php

namespace App\Models;

use Database\Factories\WishlistFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wishlist extends Model
{
    /** @use HasFactory<WishlistFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'description', 'is_public'];

    protected function casts(): array
    {
        return ['user_id' => 'integer', 'is_public' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function sharedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'wishlist_user')->withPivot('access')->withTimestamps();
    }

    public function sharedDepartments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'wishlist_department')->withPivot('access')->withTimestamps();
    }

    public function scopeAccessibleTo(Builder $query, User $user, bool $editable = false): Builder
    {
        if ($user->can('manage-all-Wishlist')) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user, $editable): void {
            $query->where('user_id', $user->id)
                ->orWhereHas('sharedUsers', function (Builder $query) use ($user, $editable): void {
                    $query->where('users.id', $user->id);
                    if ($editable) {
                        $query->where('wishlist_user.access', 'edit');
                    }
                })
                ->orWhereHas('sharedDepartments', function (Builder $query) use ($user, $editable): void {
                    $query->whereIn('departments.id', $user->departments()->select('departments.id'));
                    if ($editable) {
                        $query->where('wishlist_department.access', 'edit');
                    }
                });
        });
    }
}
