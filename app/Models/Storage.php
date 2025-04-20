<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string|null $contact_details
 * @property string $country
 * @property string $street
 * @property string $city
 * @property string $post_code
 * @property string|null $comment
 * @property string|null $documents
 * @property int $added_by
 * @property int $edited_by
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Storage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Storage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Storage onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Storage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Storage whereAddedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Storage whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Storage whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Storage whereContactDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Storage whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Storage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Storage whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Storage whereDocuments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Storage whereEditedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Storage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Storage whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Storage wherePostCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Storage whereStreet($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Storage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Storage withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Storage withoutTrashed()
 * @mixin \Eloquent
 */
class Storage extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'contact_details', 'country', 'street', 'city', 'post_code', 'added_by', 'edited_by', 'comment', 'documents'];

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
}
