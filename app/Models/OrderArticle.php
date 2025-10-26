<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $picture
 * @property User|null $added_by
 * @property User|null $edited_by
 * @property int|null $category
 * @property float $price_net
 * @property float $price_gross
 * @property string $currency
 * @property string|null $url
 * @property string|null $comment
 * @property string|null $article_number
 * @property float $tax_rate
 * @property float $returning_deposit
 * @property int $locked
 * @property string|null $locked_reason
 * @property int $quantity_available
 * @property array<array-key, mixed>|null $article_variants
 * @property float $packaging_size_per_article
 * @property int|null $packaging_size_per_article_unit
 * @property int $packaging_article_quantity
 * @property string|null $deadline
 * @property bool $auto_calculate
 * @property string|null $important_note
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read OrderCategory|null $categorie
 * @method static Builder<static>|OrderArticle newModelQuery()
 * @method static Builder<static>|OrderArticle newQuery()
 * @method static Builder<static>|OrderArticle onlyTrashed()
 * @method static Builder<static>|OrderArticle query()
 * @method static Builder<static>|OrderArticle whereAddedBy($value)
 * @method static Builder<static>|OrderArticle whereArticleNumber($value)
 * @method static Builder<static>|OrderArticle whereArticleVariants($value)
 * @method static Builder<static>|OrderArticle whereAutoCalculate($value)
 * @method static Builder<static>|OrderArticle whereCategory($value)
 * @method static Builder<static>|OrderArticle whereComment($value)
 * @method static Builder<static>|OrderArticle whereCreatedAt($value)
 * @method static Builder<static>|OrderArticle whereCurrency($value)
 * @method static Builder<static>|OrderArticle whereDeadline($value)
 * @method static Builder<static>|OrderArticle whereDeletedAt($value)
 * @method static Builder<static>|OrderArticle whereDescription($value)
 * @method static Builder<static>|OrderArticle whereEditedBy($value)
 * @method static Builder<static>|OrderArticle whereId($value)
 * @method static Builder<static>|OrderArticle whereImportantNote($value)
 * @method static Builder<static>|OrderArticle whereLocked($value)
 * @method static Builder<static>|OrderArticle whereLockedReason($value)
 * @method static Builder<static>|OrderArticle whereName($value)
 * @method static Builder<static>|OrderArticle wherePackagingArticleQuantity($value)
 * @method static Builder<static>|OrderArticle wherePackagingSizePerArticle($value)
 * @method static Builder<static>|OrderArticle wherePackagingSizePerArticleUnit($value)
 * @method static Builder<static>|OrderArticle wherePicture($value)
 * @method static Builder<static>|OrderArticle wherePriceGross($value)
 * @method static Builder<static>|OrderArticle wherePriceNet($value)
 * @method static Builder<static>|OrderArticle whereQuantityAvailable($value)
 * @method static Builder<static>|OrderArticle whereReturningDeposit($value)
 * @method static Builder<static>|OrderArticle whereTaxRate($value)
 * @method static Builder<static>|OrderArticle whereUpdatedAt($value)
 * @method static Builder<static>|OrderArticle whereUrl($value)
 * @method static Builder<static>|OrderArticle withTrashed()
 * @method static Builder<static>|OrderArticle withoutTrashed()
 * @mixin \Eloquent
 */
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
