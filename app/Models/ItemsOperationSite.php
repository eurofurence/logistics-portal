<?php

namespace App\Models;

use App\Models\Department;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
