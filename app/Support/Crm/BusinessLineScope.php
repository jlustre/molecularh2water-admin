<?php

namespace App\Support\Crm;

use App\Models\User;
use App\Support\BusinessLineContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;

class BusinessLineScope
{
    /**
     * @param  Builder<mixed>|Relation<mixed, mixed, *>  $query
     * @return Builder<mixed>|Relation<mixed, mixed, *>
     */
    public static function apply(Builder|Relation $query, ?User $user = null, string $column = 'business_line'): Builder|Relation
    {
        $model = $query->getModel();

        if (! Schema::hasColumn($model->getTable(), $column)) {
            return $query;
        }

        $values = BusinessLineContext::valuesForQuery($user);

        if ($values === []) {
            return $query->whereRaw('0 = 1');
        }

        if (count($values) === 1) {
            return $query->where(function (Builder $inner) use ($column, $values) {
                $inner->where($column, $values[0])
                    ->orWhere($column, 'both');
            });
        }

        return $query->where(function (Builder $inner) use ($column, $values) {
            $inner->whereIn($column, $values)
                ->orWhere($column, 'both');
        });
    }
}
