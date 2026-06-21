<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property-read Model|\Eloquent $model
 * @property-read User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StatusHistory query()
 *
 * @mixin \Eloquent
 */
class StatusHistory extends Model
{
    protected $fillable = [
        'icon',
        'title',
        'description',
        'user_id',
        'model_type',
        'model_id',
    ];

    protected $casts = [
        'description' => 'array',
    ];

    public function model()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
