<?php

namespace App\Models;

use Filament\Actions\Action;
use Illuminate\Support\Carbon;
use Database\Factories\OrderRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use App\Notifications\GeneralNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Filament\Notifications\Notification as FilamentNotification;

/**
 * @property int $id
 * @property string $title
 * @property string|null $message
 * @property string|null $comment
 * @property string|null $url
 * @property int $status
 * @property int $status_notifications
 * @property Carbon|null $deleted_at
 * @property int $quantity
 * @property int $order_event_id
 * @property int $department_id
 * @property int $added_by
 * @property int $edited_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $addedBy
 * @property-read Department|null $department
 * @property-read User|null $editedBy
 * @property-read OrderEvent|null $event
 * @method static OrderRequestFactory factory($count = null, $state = [])
 * @method static Builder<static>|OrderRequest newModelQuery()
 * @method static Builder<static>|OrderRequest newQuery()
 * @method static Builder<static>|OrderRequest onlyTrashed()
 * @method static Builder<static>|OrderRequest query()
 * @method static Builder<static>|OrderRequest whereAddedBy($value)
 * @method static Builder<static>|OrderRequest whereComment($value)
 * @method static Builder<static>|OrderRequest whereCreatedAt($value)
 * @method static Builder<static>|OrderRequest whereDeletedAt($value)
 * @method static Builder<static>|OrderRequest whereDepartmentId($value)
 * @method static Builder<static>|OrderRequest whereEditedBy($value)
 * @method static Builder<static>|OrderRequest whereId($value)
 * @method static Builder<static>|OrderRequest whereMessage($value)
 * @method static Builder<static>|OrderRequest whereOrderEventId($value)
 * @method static Builder<static>|OrderRequest whereQuantity($value)
 * @method static Builder<static>|OrderRequest whereStatus($value)
 * @method static Builder<static>|OrderRequest whereStatusNotifications($value)
 * @method static Builder<static>|OrderRequest whereTitle($value)
 * @method static Builder<static>|OrderRequest whereUpdatedAt($value)
 * @method static Builder<static>|OrderRequest whereUrl($value)
 * @method static Builder<static>|OrderRequest withTrashed()
 * @method static Builder<static>|OrderRequest withoutTrashed()
 * @mixin \Eloquent
 */
class OrderRequest extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'message',
        'comment',
        'added_by',
        'edited_by',
        'url',
        'status',
        'order_event_id',
        'department_id',
        'status_notifications',
        'quantity'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $user_id = Auth::user()->id;
            $model->added_by = $user_id;
            $model->edited_by = $user_id;

            // Checking the authorization to change moderation values
            if (!empty($model->status) || !empty($model->comment)) {
                if (!Auth::user()->can('can-moderate-order-request')) {
                    abort(403);
                }
            }
        });

        static::updating(function ($model) {
            $user = Auth::user();
            $model->edited_by = $user->id;

            if ($model->isDirty('status') || $model->isDirty('comment')) {
                if (!$user->can('can-moderate-order-request')) {
                    abort(403);
                }

                if ($model->isDirty('status')) {
                    $model_link = null;
                    $model_link = route('filament.app.resources.order-requests.view', $model);

                    if ($model->status_notifications == true) {
                        //Send email
                        Notification::send($model->addedBy, new GeneralNotification($model->addedBy->name, __('general.order_request', [], 'en') . ' #' . $model->id . ' - ' . $model->title, __('general.status_has_changed', [], 'en'), __('general.status_has_changed_order_requested', [], 'en'), $model->title, null, $model->comment, $model_link, __('general.show', [], 'en')));

                        //Send database notification
                        FilamentNotification::make()
                            ->title(__('general.order_request'))
                            ->body(__('general.status_has_changed') . ': ' . $model->title)
                            ->icon('heroicon-o-chat-bubble-left-ellipsis')
                            ->iconColor('info')
                            ->actions([
                                Action::make(__('general.mark_as_unread'))
                                    ->markAsUnread(),
                                Action::make(__('general.mark_as_read'))
                                    ->markAsRead(),
                                Action::make(__('general.show'))
                                    ->url(route('filament.app.resources.order-requests.view', $model))
                                    ->button()
                            ])
                            ->sendToDatabase($model->addedBy);
                    }
                }
            }
        });
    }

    public function addedBy(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'added_by');
    }

    public function editedBy(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'edited_by');
    }

    public function event(): HasOne
    {
        return $this->hasOne(OrderEvent::class, 'id', 'order_event_id');
    }

    public function department(): HasOne
    {
        return $this->hasOne(Department::class, 'id', 'department_id');
    }
}
