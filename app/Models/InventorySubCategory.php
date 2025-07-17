<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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

        #TODO: Sollte das Department mal im Nachhinein geändert werden müssen hier Checks eingebaut werden die Prüfen ob die Kategorie/operation site zum Department gehört
        static::creating(function ($model) {
            $model->added_by = Auth::user()->id;
            $model->edited_by = Auth::user()->id;
        });

        #TODO: Sollte das Department mal im Nachhinein geändert werden müssen hier Checks eingebaut werden die Prüfen ob die Kategorie/operation site zum Department gehört
        static::updating(function ($model) {
            $model->edited_by = Auth::user()->id;
        });
    }

     /**
     * The department that belong to the operation site.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department');
    }
}
