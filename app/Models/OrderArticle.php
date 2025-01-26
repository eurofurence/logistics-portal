<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class OrderArticle extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'picture',
        'added_by',
        'edited_by',
        'price_net',
        'price_gross',
        'article_number',
        'url',
        'comment',
        'currency',
        'tax_rate',
        'category',
        'returning_deposit',
        'locked',
        'locked_reason',
        'quantity_available',
        'article_variants',
        'packaging_size_per_article',
        'packaging_size_per_article_unit',
        'packaging_article_quantity',
        'deadline',
        'auto_calculate',
        'important_note'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price_net' => 'real',
        'price_gross' => 'real',
        'tax_rate' => 'real',
        'returning_deposit' => 'real',
        'article_variants' => 'array',
        'packaging_size_per_article' => 'real',
        'auto_calculate' => 'bool'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $user = static::getAuthUser();

            $model->added_by = $user->id;
            $model->edited_by = $user->id;
        });

        static::updating(function ($model) {
            $user = static::getAuthUser();
            $model->edited_by = $user->id;

            if ($model->isDirty(['price_net', 'price_gross', 'tax_rate', 'url', 'name', 'description', 'picture', 'edited_by', 'currency', 'article_number', 'returning_deposit'])) {
                $updated_data = [
                    'price_net' => $model->price_net,
                    'price_gross' => $model->price_gross,
                    'tax_rate' => $model->tax_rate,
                    'url' => $model->url,
                    'name' => $model->name,
                    'description' => $model->description,
                    'picture' => $model->picture,
                    'edited_by' => $user->id,
                    'currency' => $model->currency,
                    'article_number' => $model->article_number,
                    'returning_deposit' => $model->returning_deposit,
                ];

                $order = Order::where('order_article_id', $model->id)
                    ->whereHas('event', function ($query) {
                        $query->where('locked', false);
                    })->where('status', 'open')
                    ->update($updated_data);
            }
        });
    }

    public function added_by(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'added_by');
    }

    public function edited_by(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'edited_by');
    }

    public function categorie(): HasOne
    {
        return $this->hasOne(OrderCategory::class, 'id', 'category');
    }

    /**
     * The function `getAuthUser` returns the authenticated user or the "System" user if no user is authenticated.
     *
     * @return User The `getAuthUser` function returns an instance of the `User` model. If a user is authenticated, it
     * returns the authenticated user using `Auth::user()`. If no user is authenticated, it returns the "System" user by
     * finding the user with an ID of 0 using `User::find(0)`.
     */
    private static function getAuthUser(): User
    {
        $user = Auth::check() ? Auth::user() : null;

        // If no user is authenticated, select the "System" user
        if (!$user) {
            $user = User::find(0);
        }

        return $user;
    }
}
