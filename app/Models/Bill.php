<?php

namespace App\Models;

use App\Models\User;
use App\Models\Department;
use App\Models\OrderEvent;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use App\Notifications\GeneralNotification;
use Filament\Notifications\Actions\Action;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Support\Facades\Notification;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Filament\Notifications\Notification as FilamentNotification;

/**
 * @property int $id
 * @property string $title
 * @property string $description
 * @property float $value
 * @property string $status
 * @property string|null $comment
 * @property string $currency
 * @property float|null $advance_payment_value
 * @property string|null $advance_payment_receiver
 * @property int $department_id
 * @property int $order_event_id
 * @property int $added_by
 * @property int $edited_by
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User|null $addedBy
 * @property-read Department|null $department
 * @property-read User|null $editedBy
 * @property-read OrderEvent|null $event
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, Media> $media
 * @property-read int|null $media_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bill newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bill newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bill onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bill query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bill whereAddedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bill whereAdvancePaymentReceiver($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bill whereAdvancePaymentValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bill whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bill whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bill whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bill whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bill whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bill whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bill whereEditedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bill whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bill whereOrderEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bill whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bill whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bill whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bill whereValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bill withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bill withoutTrashed()
 * @property string|null $repayment_method
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Bill whereRepaymentMethod($value)
 * @mixin \Eloquent
 */
class Bill extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'description',
        'comment',
        'value',
        'currency',
        'status',
        'title',
        'order_event_id',
        'department_id',
        'added_by',
        'edited_by',
        'advance_payment_value',
        'advance_payment_receiver',
        'repayment_method',
        'exchange_rate',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value' => 'real',
        'advance_payment_value' => 'real'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->added_by = Auth::user()->id;
            $model->edited_by = Auth::user()->id;
        });

        static::created(function ($model) {
            $users_to_notify = User::permission('get-new-bill-accountant-notification')->get();

            if (!empty($users_to_notify)) {
                $model_link = null;
                $model_link = route('filament.app.resources.bills.view', $model);

                foreach ($users_to_notify as $user_to_notify) {
                    //Send email
                    Notification::send($user_to_notify, new GeneralNotification(username: $user_to_notify->name, subject: __('general.bill', [], 'en') . ' #' . $model->id . ' - ' . $model->title, titel: __('general.new_bill_is_available', [], 'en'), message: __('general.new_bill_is_available', [], 'en'), details_title: $model->title, details_title_hint: null, details_message: __('general.department') . ': ' . $model->connected_department->name, details_link: $model_link, details_link_title: __('general.show', [], 'en')));

                    //Send database notification
                    FilamentNotification::make()
                        ->title(__('general.bill'))
                        ->body(__('general.new_bill_is_available') . ': ' . $model->title)
                        ->icon('heroicon-o-chat-bubble-left-ellipsis')
                        ->iconColor('info')
                        ->actions([
                            Action::make(__('general.mark_as_unread'))
                                ->markAsUnread(),
                            Action::make(__('general.mark_as_read'))
                                ->markAsRead(),
                            Action::make(__('general.show'))
                                ->url(route('filament.app.resources.bills.view', $model))
                                ->button()
                        ])
                        ->sendToDatabase($user_to_notify);
                }
            }
        });

        static::updating(function ($model) {
            $user = Auth::user();
            $model->edited_by = $user->id;

            if ($model->isDirty('status')) {
                if (!$user->can('can-change-bill-status')) {
                    abort(403);
                }

                if ($model->isDirty('status')) {
                    $model_link = null;
                    $model_link = route('filament.app.resources.bills.view', $model);

                    //Send email
                    Notification::send($model->addedBy, new GeneralNotification($model->addedBy->name, __('general.bill', [], 'en') . ' #' . $model->id . ' - ' . $model->title, __('general.status_has_changed', [], 'en'), __('general.status_has_changed_bill', [], 'en'), $model->title, null, null, $model_link, __('general.show', [], 'en')));

                    //Send database notification
                    FilamentNotification::make()
                        ->title(__('general.bill'))
                        ->body(__('general.status_has_changed') . ': ' . $model->title)
                        ->icon('heroicon-o-chat-bubble-left-ellipsis')
                        ->iconColor('info')
                        ->actions([
                            Action::make(__('general.mark_as_unread'))
                                ->markAsUnread(),
                            Action::make(__('general.mark_as_read'))
                                ->markAsRead(),
                            Action::make(__('general.show'))
                                ->url(route('filament.app.resources.bills.view', $model))
                                ->button()
                        ])
                        ->sendToDatabase($model->addedBy);
                }
            }
        });
    }

    public function connected_event(): HasOne
    {
        return $this->hasOne(OrderEvent::class, 'id', 'order_event_id');
    }

    public function connected_department(): HasOne
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
}
