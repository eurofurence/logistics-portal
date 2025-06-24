<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\OrderEvent;
use App\Models\OrderArticle;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
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
 * @property string|null $approved_at
 * @property int|null $approved_by
 * @property-read \App\Models\User|null $approvedBy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereApprovedBy($value)
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
        'payment_method',
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
        'order_number',
        'approved_at',
        'approved_by',
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

            //static::sendInstantDeliveryMessage($model);

            if (!empty($model->order_article_id)) {
                if (self::addToExistingOrder($model) == true) {
                    return false; // Return false to halt the creation process
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
            }

            if ($model->discount_net) {
                if ($model->discount_net > 0) {
                    if ($model->discount_net > ($model->amount * $model->price_net) || $model->discount_net > config('constants.inputs.numeric.max')) {
                        throw new \Exception(__('general.discount_limit_exceeded'));
                    }
                }
            }

            if ($model->isDirty('approved_by')) {
                $model->approved_at = Carbon::now();
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

    /**
     * Adds an article to an existing order or updates the existing order if an entry with the same IDs already exists.
     *
     * This function checks for an existing order with the same order_article_id, department_id, and order_event_id,
     * excluding the current model if it has an ID. If such an order is found, it updates the amount, prices, comment,
     * and article number. It also handles the approval status based on user permissions. If $just_amount is true,
     * only the amount is updated. If the model exists, it is locked and deleted after updating the existing order.
     *
     * @param object $model The model object containing the article data to be added or updated.
     * @param bool $overwrite_approval_check Optional. If set to true, skips the approval check. Defaults to false.
     * @param bool $just_amount Optional. If set to true, only the amount is updated. Defaults to false.
     *
     * @return bool Returns true if an existing order was found and updated, false otherwise.
     */
    public static function addToExistingOrder($model, bool $overwrite_approval_check = false, bool $just_amount = false): bool
    {
        // Check whether an entry with the same IDs already exists, excluding the current model if it has an ID
        $query = Order::where('order_article_id', $model->order_article_id)
            ->where('department_id', $model->department_id)
            ->where('order_event_id', $model->order_event_id)
            ->where('status', 'open');

        if ($model->exists) {
            $query->where('id', '!=', $model->id);
        }

        $existingOrder = $query->first();


        if ($existingOrder) {
            // If available, increase the amount, change the price and cancel the creation process
            $existingOrder->amount += $model->amount;

            if ($just_amount == false) {
                $existingOrder->price_net = $model->price_net;
                $existingOrder->price_gross = $model->price_gross;
                $existingOrder->comment = $existingOrder->comment . "\n" . $model->comment;
                $existingOrder->article_number = $model->article_number;
            }

            if ($overwrite_approval_check == false) {
                if (Auth::user()->hasDepartmentRoleWithPermissionTo('order-needs-approval', $model->department_id)) {
                    $existingOrder->status = 'awaiting_approval';
                }
            }

            $existingOrder->save();

            if ($model->exists) {
                $model->status = 'locked';
                $model->save();
                $model->delete();
            }

            $model->added_to_existing = true;
            return true;
        }

        return false;
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

    public function approvedBy(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'approved_by');
    }

    public function directoryArticle(): HasOne
    {
        return $this->hasOne(OrderArticle::class, 'id', 'order_article_id');
    }

    public function orderRequest(): HasOne
    {
        return $this->hasOne(OrderRequest::class, 'id', 'order_request_id');
    }

    /**
     * Approves the order if it can be approved.
     *
     * This function checks if the order can be approved using the canBeApproved() function.
     * If it can be approved, it updates the order's status to 'open' and sets the
     * 'approved_by' field to the current authenticated user's ID.
     *
     * @return bool Returns true if the order was successfully approved, false otherwise.
     */
    public function approve(): bool
    {
        if ($this->canBeApproved()) {
            $this->update(['status' => 'open', 'approved_by' => Auth::id()]);

            if (!empty($this->order_article_id)) {
                self::addToExistingOrder($this, just_amount: true);
            }

            return true;
        }

        return false;
    }

    /**
     * Declines the order by deleting it if it can be declined.
     *
     * This function attempts to decline the order by first checking if it can be declined
     * using the canBeDeclined() method. If the order can be declined, it is deleted from
     * the database.
     *
     * @return bool Returns true if the order was successfully declined and deleted,
     * false if the order could not be declined.
     */
    public function decline(): bool
    {
        if ($this->canBeDeclined()) {
            $this->delete();

            return true;
        }

        return false;
    }

    /**
     * The function `canBeApproved` checks if an order with status 'awaiting_approval' can be approved based on the user's
     * permission.
     *
     * @return bool The function `canBeApproved()` returns a boolean value. It returns `true` if the status of the object
     * is 'awaiting_approval' and the current user has permission to approved the order. Otherwise, it returns `false`.
     */
    public function canBeApproved(): bool
    {
        if ($this->status == 'awaiting_approval') {
            if (empty($this->deleted_at)) {
                if (Gate::check('approveOrder', [$this])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * The function `canBeDeclined` checks if an order with status 'awaiting_approval' can be declined based on the user's
     * permission.
     *
     * @return bool The function `canBeDeclined()` returns a boolean value. It returns `true` if the status of the object
     * is 'awaiting_approval' and the current user has permission to decline the order. Otherwise, it returns `false`.
     */
    public function canBeDeclined(): bool
    {
        if ($this->status == 'awaiting_approval') {
            if (empty($this->deleted_at)) {
                if (Gate::check('declineOrder', [$this])) {
                    return true;
                }
            }
        }

        return false;
    }
}
