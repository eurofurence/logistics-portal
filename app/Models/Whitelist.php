<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 *
 *
 * @property int $id
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $user
 * @property-read \App\Models\User|null $users
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Whitelist newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Whitelist newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Whitelist query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Whitelist whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Whitelist whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Whitelist whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Whitelist whereUpdatedAt($value)
  * @mixin \Eloquent
 */
class Whitelist extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'whitelist';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['email'];

    public function users()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function user() :BelongsTo
    {
        return $this->belongsTo(User::class, 'email', 'email');
    }
}
