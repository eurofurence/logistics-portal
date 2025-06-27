<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
