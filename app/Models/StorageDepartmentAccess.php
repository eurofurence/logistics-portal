<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $department
 * @property int $storage
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder<static>|StorageDepartmentAccess newModelQuery()
 * @method static Builder<static>|StorageDepartmentAccess newQuery()
 * @method static Builder<static>|StorageDepartmentAccess query()
 * @method static Builder<static>|StorageDepartmentAccess whereCreatedAt($value)
 * @method static Builder<static>|StorageDepartmentAccess whereDepartment($value)
 * @method static Builder<static>|StorageDepartmentAccess whereId($value)
 * @method static Builder<static>|StorageDepartmentAccess whereStorage($value)
 * @method static Builder<static>|StorageDepartmentAccess whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class StorageDepartmentAccess extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'storage_department';

    protected $fillable = ['department', 'storage'];
}
