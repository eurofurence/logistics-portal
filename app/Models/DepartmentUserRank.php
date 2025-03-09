<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepartmentUserRank extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['local_role', 'idp_group', 'active', 'name'];

    protected $table = 'department_userrank';


}
