<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property int $department_id
 * @property int $user_id
 * @property int|null $role_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Department $department
 * @property-read Role|null $role
 * @property-read User $user
 * @method static Builder<static>|DepartmentMember newModelQuery()
 * @method static Builder<static>|DepartmentMember newQuery()
 * @method static Builder<static>|DepartmentMember query()
 * @method static Builder<static>|DepartmentMember whereCreatedAt($value)
 * @method static Builder<static>|DepartmentMember whereDepartmentId($value)
 * @method static Builder<static>|DepartmentMember whereId($value)
 * @method static Builder<static>|DepartmentMember whereRoleId($value)
 * @method static Builder<static>|DepartmentMember whereUpdatedAt($value)
 * @method static Builder<static>|DepartmentMember whereUserId($value)
 * @mixin \Eloquent
 */
class DepartmentMember extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'department_user';

    protected $fillable = ['department_id', 'user_id', 'role_id'];

    /**
     * Get the department that the member belongs to.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * Get the user that is a member of the department.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}
