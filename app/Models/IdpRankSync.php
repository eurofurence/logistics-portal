<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
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
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Role|null $role
 * @method static Builder<static>|IdpRankSync newModelQuery()
 * @method static Builder<static>|IdpRankSync newQuery()
 * @method static Builder<static>|IdpRankSync onlyTrashed()
 * @method static Builder<static>|IdpRankSync query()
 * @method static Builder<static>|IdpRankSync whereActive($value)
 * @method static Builder<static>|IdpRankSync whereCreatedAt($value)
 * @method static Builder<static>|IdpRankSync whereDeletedAt($value)
 * @method static Builder<static>|IdpRankSync whereId($value)
 * @method static Builder<static>|IdpRankSync whereIdpGroup($value)
 * @method static Builder<static>|IdpRankSync whereLocalRole($value)
 * @method static Builder<static>|IdpRankSync whereName($value)
 * @method static Builder<static>|IdpRankSync whereUpdatedAt($value)
 * @method static Builder<static>|IdpRankSync withTrashed()
 * @method static Builder<static>|IdpRankSync withoutTrashed()
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
