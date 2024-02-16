<?php

namespace Luminix\Backend\Model;

trait QueryScopes
{

    public function scopeBeforeLuminix($query, $request)
    {}

    public function scopeAfterLuminix($query, $request)
    {}

    public function scopeAllowed($query, $permission)
    {}

    public function scopeSearch($query, $search)
    {
        foreach ($this->getFillable() as $fillable) {
            $query->orWhere($fillable, 'like', '%' . implode('%', explode(' ', $search)) . '%');
        }
    }

    public function scopeWhereBelongsToTab($query, $tab)
    {
        if ('trashed' == $tab) {
            $query->onlyTrashed();
        }
    }

    public function scopeWhereMatchesFilter($query, $filters)
    {}

    public function scopeApplyOrderBy($query, $column, $direction)
    {
        $query->orderBy($column, $direction)->orderBy($this->getKeyName(), 'asc');
    }

    public function scopeLuminixQuery($query, $request, $permission)
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

        if ($request->has('filters')) {
            $query->where(function ($query) use ($request) {
                $query->whereMatchesFilter($request->filters);
            });
        }

        if ($request->has('tab')) {
            $query->where(function ($query) use ($request) {
                $query->whereBelongsToTab($request->tab);
            });
        }

        if ($request->has('order_by')) {
            [$field, $direction] = explode(':', $request->order_by);
            $query->applyOrderBy($field, $direction);
        }

        $query->afterLuminix($request);
    }

}
