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
                    throw new \Exception(__('general.no_permission_order_status'));
                }
            }

            //static::sendInstantDeliveryMessage($model);

            if (!empty($model->order_article_id)) {
                // Check whether an entry with the same IDs already exists
                $existingOrder = Order::where('order_article_id', $model->order_article_id)
                    ->where('department_id', $model->department_id)
                    ->where('order_event_id', $model->order_event_id)
                    ->where('status', 'open')
                    ->first();

                if ($existingOrder) {
                    // If available, increase the amount, change the price and cancel the creation process
                    $existingOrder->amount += $model->amount;
                    $existingOrder->price_net = $model->price_net;
                    $existingOrder->price_gross = $model->price_gross;
                    $existingOrder->comment = $existingOrder->comment . "\n" . $model->comment;
                    $existingOrder->article_number = $model->article_number;
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
                    throw new \Exception(__('general.no_permission_order_status'));
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
