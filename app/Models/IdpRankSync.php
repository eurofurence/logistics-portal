<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property string|null $name
 * @property int $local_role
 * @property string $idp_group
 * @property int $active
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Role|null $role
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdpRankSync newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdpRankSync newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdpRankSync onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdpRankSync query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdpRankSync whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdpRankSync whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdpRankSync whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdpRankSync whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdpRankSync whereIdpGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdpRankSync whereLocalRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdpRankSync whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdpRankSync whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdpRankSync withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdpRankSync withoutTrashed()
 * @mixin \Eloquent
 */
class IdpRankSync extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['local_role', 'idp_group', 'active', 'name'];

    public function role(): HasOne
    {
        return $this->hasOne(Role::class, 'id', 'local_role');
    }
}
