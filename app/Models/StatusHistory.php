<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusHistory extends Model
{
    protected $fillable = [
        'icon',
        'title',
        'description',
        'user_id',
        'model_type',
        'model_id'
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
