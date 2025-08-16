<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $department
 * @property int $storage
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageDepartmentAccess newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageDepartmentAccess newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageDepartmentAccess query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageDepartmentAccess whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageDepartmentAccess whereDepartment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageDepartmentAccess whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageDepartmentAccess whereStorage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageDepartmentAccess whereUpdatedAt($value)
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
