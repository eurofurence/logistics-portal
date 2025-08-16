<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property string $name
 * @property int $department
 * @property int $added_by
 * @property int $edited_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Department|null $connected_department
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventorySubCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventorySubCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventorySubCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventorySubCategory whereAddedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventorySubCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventorySubCategory whereDepartment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventorySubCategory whereEditedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventorySubCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventorySubCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventorySubCategory whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class InventorySubCategory extends Model
{
    use HasFactory;

    protected $table = 'inventory_sub_category';

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

        #TODO: Checks einbauen
        #TODO: Sollte das Department mal im Nachhinein geändert werden müssen hier Checks eingebaut werden die Prüfen ob die Kategorie/operation site zum Department gehört
        static::creating(function ($model) {
            $model->added_by = Auth::user()->id;
            $model->edited_by = Auth::user()->id;
        });

        #TODO: Checks einbauen
        #TODO: Sollte das Department mal im Nachhinein geändert werden müssen hier Checks eingebaut werden die Prüfen ob die Kategorie/operation site zum Department gehört
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
