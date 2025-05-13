<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\OrderEvent;
use App\Models\OrderArticle;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 *
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $delivery_provider
 * @property string|null $delivery_by
 * @property string|null $delivery_destination
 * @property string|null $tracking_number
 * @property string|null $delivery_date
 * @property float $delivery_costs
 * @property int $instant_delivery
 * @property int $department_id
 * @property int $added_by
 * @property int $edited_by
 * @property int $amount
 * @property float $price_net
 * @property float $price_gross
 * @property float $tax_rate
 * @property string|null $payment_method
 * @property string $currency
 * @property string|null $url
 * @property string|null $contact
 * @property string|null $tags
 * @property int $dangerous_good
 * @property int $big_size
 * @property int $needs_truck
 * @property string|null $ordered_at
 * @property int $booked_to_inventory
 * @property int|null $inv_id
 * @property int $order_event_id
 * @property string|null $comment
 * @property string $status
 * @property string|null $picture
 * @property int|null $order_article_id
 * @property string|null $article_number
 * @property string|null $user_note
 * @property int|null $order_request_id
 * @property int $special_delivery
 * @property string|null $special_flag_text
 * @property float $returning_deposit
 * @property float|null $discount_net
 * @property string|null $order_number
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $addedBy
 * @property-read \App\Models\Department|null $department
 * @property-read OrderArticle|null $directoryArticle
 * @property-read \App\Models\User|null $editedBy
 * @property-read OrderEvent|null $event
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, \Spatie\MediaLibrary\MediaCollections\Models\Media> $media
 * @property-read int|null $media_count
 * @property-read \App\Models\OrderRequest|null $orderRequest
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereAddedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereArticleNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereBigSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereBookedToInventory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereContact($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereDangerousGood($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereDeliveryBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereDeliveryCosts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereDeliveryDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereDeliveryDestination($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereDeliveryProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereDiscountNet($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereEditedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereInstantDelivery($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereInvId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereNeedsTruck($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereOrderArticleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereOrderEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereOrderNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereOrderRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereOrderedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order wherePicture($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order wherePriceGross($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order wherePriceNet($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereReturningDeposit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereSpecialDelivery($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereSpecialFlagText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereTags($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereTaxRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereTrackingNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereUserNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order withoutTrashed()
 * @mixin \Eloquent
 */
class Order extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'delivery_provider',
        'delivery_by',
        'delivery_destination',
        'tracking_number',
        'delivery_date',
        'instant_delivery',
        'delivered',
        'department_id',
        'added_by',
        'edited_by',
        'amount',
        'price_net',
        'price_gross',
        'tax_rate',
        'payment_methode',
        'currency',
        'url',
        'contact',
        'tags',
        'is_active',
        'dangerous_good',
        'big_size',
        'needs_truck',
        'ordered_at',
        'booked_to_inventory',
        'inv_id',
        'order_event_id',
        'comment',
        'status',
        'picture',
        'order_article_id',
        'delivery_costs',
        'article_number',
        'user_note',
        'order_request_id',
        'special_delivery',
        'special_flag_text',
        'returning_deposit',
        'discount_net',
        'order_number'
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
        'delivery_costs' => 'real',
        'discount_net' => 'real',
    ];

    #Not Used
    protected static function sendInstantDeliveryMessage($model)
    {
        if ($model->isDirty('instant_delivery') && $model->getOriginal('instant_delivery') == 0 && $model->instant_delivery == 1) {
            if ($model->instant_delivery == true) {
                $usersWithPermission = User::permission('instant-delivery-notification')->get();

                if ($usersWithPermission) {
                    Notification::make()
                        ->title(__('general.new_instant_delivery'))
                        ->body($model->name)
                        ->icon('heroicon-o-shopping-cart')
                        ->iconColor('info')
                        ->actions([
                            Action::make(__('general.mark_as_unread'))
                                ->markAsUnread(),
                            Action::make(__('general.mark_as_read'))
                                ->markAsRead(),
                            Action::make(__('general.edit'))
                                ->url(route('filament.app.resources.orders.edit', $model), shouldOpenInNewTab: true)
                                ->button()
                                ->visible(Auth::user()->can('update-Order'))
                        ])
                        ->sendToDatabase($usersWithPermission);
                }
            }
        }
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->added_by = Auth::user()->id;
            $model->edited_by = Auth::user()->id;

            // Checking the authorization to change the status
            if (!empty($model->status)) {
                if (!Auth::user()->can('can-change-order-status')) {
                    if ($model->status != 'awaiting_approval' || 'open') {
                        throw new \Exception(__('middleware.no_permission_order_status'));
                    }
                }
            }

            //static::sendInstantDeliveryMessage($model);

            if (!empty($model->order_article_id)) {
                // Check whether an entry with the same IDs already exists
                $existingOrder = Order::where('order_article_id', $model->order_article_id)
                    ->where('department_id', $model->department_id)
                    ->where('order_event_id', $model->order_event_id)
                    ->where('status', 'open')
                    //->orWhere('status', 'awaiting_approval')
                    ->first();

                if ($existingOrder) {
                    // If available, increase the amount, change the price and cancel the creation process
                    $existingOrder->amount += $model->amount;
                    $existingOrder->price_net = $model->price_net;
                    $existingOrder->price_gross = $model->price_gross;
                    $existingOrder->comment = $existingOrder->comment . "\n" . $model->comment;
                    $existingOrder->article_number = $model->article_number;

                    if (Auth::user()->hasDepartmentRoleWithPermissionTo('order-needs-approval', $model->department_id)) {
                        $existingOrder->status = 'awaiting_approval';
                    }

                    $existingOrder->save();

                    $model->added_to_existing = true;
                    return false;  // This interrupts the creation process
                }
            }
        });

        static::created(function ($model) {
            //Cache::forget('orders');
        });

        static::updating(function ($model) {
            $model->edited_by = Auth::user()->id;

            if ($model->isDirty('status')) {
                if (!Auth::user()->can('can-change-order-status')) {
                    if ($model->status != 'awaiting_approval') {
                        if ($model->status != 'open') {
                            throw new \Exception(__('middleware.no_permission_order_status'));
                        }
                    }
                }

                if ($model->status == 'ordered') {
                    if (empty($model->ordered_at)) {
                        $model->ordered_at = Carbon::now();
                    }
                }

                if ($model->status == 'delivered') {
                    if (empty($model->delivery_date)) {
                        $model->delivery_date = Carbon::now();
                    }
                }


                if ($model->discount_net) {
                    if ($model->discount_net > 0) {
                        if ($model->discount_net > ($model->amount * $model->price_net) || $model->discount_net > config('constants.inputs.numeric.max')) {
                            throw new \Exception(__('general.discount_limit_exceeded'));
                        }
                    }
                }
            }

            static::sendInstantDeliveryMessage($model);
        });

        static::updated(function ($post) {
            //Cache::forget('orders');
        });

        static::deleted(function ($model) {
            //Cache::forget('orders');
        });
    }

    public function event(): HasOne
    {
        return $this->hasOne(OrderEvent::class, 'id', 'order_event_id');
    }

    public function department(): HasOne
    {
        return $this->hasOne(Department::class, 'id', 'department_id');
    }

    public function addedBy(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'added_by');
    }

    public function editedBy(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'edited_by');
    }

    public function directoryArticle(): HasOne
    {
        return $this->hasOne(OrderArticle::class, 'id', 'order_article_id');
    }

    public function orderRequest(): HasOne
    {
        return $this->hasOne(OrderRequest::class, 'id', 'order_request_id');
    }
}
