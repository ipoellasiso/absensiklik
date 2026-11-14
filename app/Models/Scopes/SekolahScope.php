<?php

namespace App\Models\Scopes;

use App\Support\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SekolahScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // Tanpa konteks sekolah, tidak ada data yang terlihat (aman secara bawaan).
        $builder->where($model->getTable().'.sekolah_id', Tenant::id() ?? 0);
    }
}