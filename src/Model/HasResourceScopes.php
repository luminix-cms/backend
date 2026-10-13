<?php

namespace Luminix\Backend\Model;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Luminix\Backend\Exceptions\InvalidFilterException;
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
            if (in_array($fillable, $this->getHidden())) {
                continue;
            }
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
        if (config('luminix.backend.api.filter.enable', true)) {
            $filter = new ModelFilter(static::class, $filters);
            $filter->apply($query);
        }
    }

    /**
     * Keys `order_by` accepts besides the model's columns, such as a
     * `withCount` alias added in scopeBeforeLuminix.
     */
    public function getOrderable(): array
    {
        if (!isset($this->orderable)) {
            return [];
        }

        return $this->orderable;
    }

    /**
     * Whether `order_by` may sort by `$column`: a key in `$orderable`, or a
     * table column that ModelFilter::excludes() doesn't keep out of client
     * queries. Sorting by an excluded column would let its value be inferred
     * from the order of the records in the response.
     */
    public function isOrderable(string $column): bool
    {
        if (in_array($column, $this->getOrderable(), true)) {
            return true;
        }

        return !ModelFilter::excludes($this, $column)
            && in_array($column, $this->getConnection()->getSchemaBuilder()->getColumnListing($this->getTable()), true);
    }

    public function scopeApplyOrderBy(Builder $query, string $column, string $direction)
    {
        $column = Str::snake($column);
        $direction = strtolower($direction);

        if (in_array($direction, ['asc', 'desc'], true) && $this->isOrderable($column)) {
            $query->orderBy($column, $direction);
        } elseif (config('luminix.backend.api.filter.throw', true)) {
            throw new InvalidFilterException(
                '[Luminix] Invalid order_by provided for model "' . static::class . '"' . "\n"
                . 'Column: ' . $column . "\n"
                . 'Direction: ' . $direction
            );
        } else {
            Log::warning('[Luminix] Invalid order_by provided', [
                'model' => static::class,
                'column' => $column,
                'direction' => $direction,
            ]);
        }

        $query->orderBy($this->getKeyName(), 'asc');
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
            [$field, $direction] = array_pad(explode(':', $request->order_by, 2), 2, 'asc');
            $query->applyOrderBy($field, $direction);
        }

        $query->afterLuminix($request);
    }

}
