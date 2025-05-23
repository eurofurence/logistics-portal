<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * 
 *
 * @property int $id
 * @property string $label
 * @property string|null $name
 * @property string|null $street
 * @property string|null $zip
 * @property string|null $city
 * @property string|null $country
 * @property string|null $phone
 * @property string|null $email
 * @property int $default
 * @property int $locked
 * @property string|null $comment
 * @property \App\Models\User|null $added_by
 * @property \App\Models\User|null $edited_by
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addressbook newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addressbook newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addressbook onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addressbook query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addressbook whereAddedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addressbook whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addressbook whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addressbook whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addressbook whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addressbook whereDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addressbook whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addressbook whereEditedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addressbook whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addressbook whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addressbook whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addressbook whereLocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addressbook whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addressbook wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addressbook whereStreet($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addressbook whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addressbook whereZip($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addressbook withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addressbook withoutTrashed()
 * @mixin \Eloquent
 */
class Addressbook extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'addressbook';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',

        'added_by',
        'edited_by',
        'comment',
    ];

    public static function boot()
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

    public function added_by(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'added_by');
    }

    public function edited_by(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'edited_by');
    }
}
