<?php

namespace App\Models;

use Database\Factories\WishlistFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    /** @use HasFactory<WishlistFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'description', 'is_public'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function sharedUsers()
    {
        return $this->belongsToMany(User::class, 'wishlist_user');
    }
}
