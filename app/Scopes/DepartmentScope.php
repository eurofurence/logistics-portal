<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class DepartmentScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (auth()->check()) {
            $userCatalogIds = Auth::user()->catalogs()->pluck('id');

            $builder->whereIn('id', $userCatalogIds);
        }
    }
}
