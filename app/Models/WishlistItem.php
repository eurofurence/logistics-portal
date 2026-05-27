<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WishlistItem extends Model
{
    /** @use HasFactory<\Database\Factories\WishlistItemFactory> */
    use HasFactory;

    protected $fillable = ['wishlist_id', 'order_article_id'];

    public function wishlist()
    {
        return $this->belongsTo(Wishlist::class);
    }

    public function article()
    {
        return $this->belongsTo(OrderArticle::class, 'order_article_id');
    }
}
