<?php

namespace App\Models;

use App\Events\BillCreated;
use App\Events\BillStatusChanged;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property int $id
 * @property string $title
 * @property string $description
 * @property float $value
 * @property string $status
 * @property Carbon|null $payment_deadline
 * @property Carbon|null $payment_reminder_sent_at
 * @property Carbon|null $payment_overdue_reminder_sent_at
 * @property string|null $comment
 * @property string $currency
 * @property float|null $advance_payment_value
 * @property string|null $advance_payment_receiver
 * @property int $department_id
 * @property int $order_event_id
 * @property int $added_by
 * @property int $edited_by
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $addedBy
 * @property-read Department|null $department
 * @property-read User|null $editedBy
 * @property-read OrderEvent|null $event
 * @property-read MediaCollection<int, Media> $media
 * @property-read int|null $media_count
 *
 * @method static Builder<static>|Bill newModelQuery()
 * @method static Builder<static>|Bill newQuery()
 * @method static Builder<static>|Bill onlyTrashed()
 * @method static Builder<static>|Bill query()
 * @method static Builder<static>|Bill whereAddedBy($value)
 * @method static Builder<static>|Bill whereAdvancePaymentReceiver($value)
 * @method static Builder<static>|Bill whereAdvancePaymentValue($value)
 * @method static Builder<static>|Bill whereComment($value)
 * @method static Builder<static>|Bill whereCreatedAt($value)
 * @method static Builder<static>|Bill whereCurrency($value)
 * @method static Builder<static>|Bill whereDeletedAt($value)
 * @method static Builder<static>|Bill whereDepartmentId($value)
 * @method static Builder<static>|Bill whereDescription($value)
 * @method static Builder<static>|Bill whereEditedBy($value)
 * @method static Builder<static>|Bill whereId($value)
 * @method static Builder<static>|Bill whereOrderEventId($value)
 * @method static Builder<static>|Bill whereStatus($value)
 * @method static Builder<static>|Bill whereTitle($value)
 * @method static Builder<static>|Bill whereUpdatedAt($value)
 * @method static Builder<static>|Bill whereValue($value)
 * @method static Builder<static>|Bill withTrashed()
 * @method static Builder<static>|Bill withoutTrashed()
 *
 * @property string|null $repayment_method
 *
 * @method static Builder<static>|Bill whereRepaymentMethod($value)
 *
 * @property-read Department|null $connected_department
 * @property-read OrderEvent|null $connected_event
 *
 * @mixin \Eloquent
 */
class Bill extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

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
        'payment_deadline',
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
        'advance_payment_value' => 'real',
        'payment_deadline' => 'date',
        'payment_reminder_sent_at' => 'datetime',
        'payment_overdue_reminder_sent_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->payment_reminder_sent_at = null;
            $model->payment_overdue_reminder_sent_at = null;
            $model->added_by = Auth::user()->id;
            $model->edited_by = Auth::user()->id;
        });

        static::created(function ($model) {
            BillCreated::dispatch($model);
        });

        static::updating(function ($model) {
            if ($model->isDirty('payment_deadline')) {
                $model->payment_reminder_sent_at = null;
                $model->payment_overdue_reminder_sent_at = null;
            }

            $user = Auth::user();
            $model->edited_by = $user->id;

            if ($model->isDirty('status')) {
                if (! $user->can('can-change-bill-status')) {
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

    public function statusHistories()
    {
        return $this->morphMany(StatusHistory::class, 'model');
    }

    public function statusHistory()
    {
        if ($this->relationLoaded('statusHistories')) {
            return $this->statusHistories->sortByDesc('created_at');
        }

        return $this->statusHistories()
            ->with('user')
            ->latest()
            ->get();
    }
}
