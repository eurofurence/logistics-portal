<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * 
 *
 * @property int $id
 * @property int $department_id
 * @property int $user_id
 * @property int|null $role_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Department $department
 * @property-read \App\Models\Role|null $role
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentMember newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentMember newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentMember query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentMember whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentMember whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentMember whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentMember whereRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentMember whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentMember whereUserId($value)
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
