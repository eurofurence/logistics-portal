<?php

namespace App\Models;

use App\Models\User;
use App\Models\Department;
use App\Models\OrderEvent;
use App\Events\BillCreated;
use App\Events\BillStatusChanged;
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
        'reimbursement_to_invoice_issuer',
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
            BillCreated::dispatch($model);
        });

        static::updating(function ($model) {
            $user = Auth::user();
            $model->edited_by = $user->id;

            if ($model->isDirty('status')) {
                if (!$user->can('can-change-bill-status')) {
                    abort(403);
                }

                if ($model->isDirty('status')) {
                    BillStatusChanged::dispatch($model);
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

    public function statusHistory()
    {
        return StatusHistory::query()
            ->where('model_type', Bill::class)
            ->where('model_id', $this->id)
            ->with('user')
            ->latest()
            ->get();
    }
}
