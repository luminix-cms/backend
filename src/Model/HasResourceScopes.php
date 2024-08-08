<?php

namespace Luminix\Backend\Model;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Luminix\Backend\Services\ModelFilter;
use Illuminate\Support\Str;

trait HasResourceScopes
{

    public function scopeBeforeLuminix(Builder $query, Request $request)
    {}

    public function scopeAfterLuminix(Builder $query, Request $request)
    {
    }

    public function scopeAllowed(Builder $query, string $permission)
    {}

    public function scopeSearch(Builder $query, string $search)
    {
        foreach ($this->getFillable() as $fillable) {
            $query->orWhere($fillable, 'like', '%' . implode('%', explode(' ', $search)) . '%');
        }
    }

    public function scopeWhereBelongsToTab(Builder $query, string $tab)
    {
        if ('trashed' == $tab) {
            $query->onlyTrashed();
        }
    }

    public function scopeWhereMatchesFilter(Builder $query, array $filters)
    {
        $filter = new ModelFilter(static::class, $filters);
        
        $filter->apply($query);
    }

    public function scopeApplyOrderBy(Builder $query, string $column, string $direction)
    {
        $query->orderBy(Str::snake($column), $direction)->orderBy($this->getKeyName(), 'asc');
    }

    public function scopeLuminixQuery(Builder $query, Request $request, ?string $permission)
    {
        $query->beforeLuminix($request);

        if ($permission) {
            $query->where(function ($query) use ($permission) {
                $query->allowed($permission);
            });
        }
        
        
        if ($request->has('q')) {
            $query->where(function ($query) use ($request) {
                $query->search($request->q);
            });
        }
        if ($request->has('where')) {
            $query->where(function ($query) use ($request) {
                $query->whereMatchesFilter($request->where);
            });
        }

        if ($request->has('tab')) {
            $query->whereBelongsToTab($request->tab);
        }

        if ($request->has('order_by')) {
            [$field, $direction] = explode(':', $request->order_by);
            $query->applyOrderBy($field, $direction);
        }

        $query->afterLuminix($request);
    }

}
