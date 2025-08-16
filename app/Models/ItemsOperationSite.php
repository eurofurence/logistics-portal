<?php

namespace App\Models;

use App\Models\Department;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $name
 * @property int $department
 * @property int $added_by
 * @property int $edited_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Department|null $connected_department
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemsOperationSite newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemsOperationSite newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemsOperationSite query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemsOperationSite whereAddedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemsOperationSite whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemsOperationSite whereDepartment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemsOperationSite whereEditedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemsOperationSite whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemsOperationSite whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemsOperationSite whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ItemsOperationSite extends Model
{
    use HasFactory;

    protected $table = 'items_operations_sites';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'department',
        'added_by',
        'edited_by',
        'department_id'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->added_by = Auth::user()->id;
            $model->edited_by = Auth::user()->id;
        });

        static::updating(function ($model) {
            $model->edited_by = Auth::user()->id;
        });
    }

     /**
     * The department that belong to the operation site.
     */
    public function connected_department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department');
    }
}
