<?php

namespace Luminix\Backend\Services;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Str;
use Spatie\ModelInfo\Attributes\Attribute;
use Spatie\ModelInfo\ModelInfo;

class ModelFilter {

    use Macroable;

    protected array $relations;
    protected ModelInfo $modelInfo;

    public function __construct(
        protected string $model,
        protected array $filters,
    )
    {
        // $relations = collect(array_keys());
        $instance = new $model();
        $this->relations = $instance->getRelationships();
        $this->modelInfo = ModelInfo::forModel($model);
    }

    public function relation(Builder $query, string $relation, mixed $value): Builder
    {
        if ($value === 'any') {
            return $query->has($relation);
        }
        $relatedModelAlias = $this->relations[$relation]['model'];
        $relatedModel = app(ModelFinder::class)->all()[$relatedModelAlias];

        $instance = new $relatedModel();
        return $query->whereHas($relation, function ($query) use ($value, $instance, $relation) {
            if (is_array($value)) {
                $query->whereIn($instance->getKeyName(), $value);
                return;
            }
            $query->where($instance->getKeyName(), $value);
        });
    }


    public function equals(Builder $query, string $column, mixed $value): Builder
    {
        if (is_array($value)) {
            return $query->whereIn($column, $value);
        }
        return $query->where($column, $value);
    }

    public function like(Builder $query, string $column, mixed $value): Builder
    {
        return $query->where($column, 'like', $value);
    }

    public function notEquals(Builder $query, string $column, mixed $value): Builder
    {
        if (is_array($value)) {
            return $query->whereNotIn($column, $value);
        }
        return $query->where($column, '!=', $value);
    }

    public function greaterThan(Builder $query, string $column, mixed $value): Builder
    {
        return $query->where($column, '>', $value);
    }

    public function greaterThanOrEquals(Builder $query, string $column, mixed $value): Builder
    {
        return $query->where($column, '>=', $value);
    }

    public function lessThan(Builder $query, string $column, mixed $value): Builder
    {
        return $query->where($column, '<', $value);
    }

    public function lessThanOrEquals(Builder $query, string $column, mixed $value): Builder
    {
        return $query->where($column, '<=', $value);
    }

    public function between(Builder $query, string $column, mixed $value): Builder
    {
        return $query->whereBetween($column, $value);
    }

    public function notBetween(Builder $query, string $column, mixed $value): Builder
    {
        return $query->whereNotBetween($column, $value);
    }

    public function null(Builder $query, string $column): Builder
    {
        return $query->whereNull($column);
    }

    public function notNull(Builder $query, string $column): Builder
    {
        return $query->whereNotNull($column);
    }

    private function methodExists(string $method): bool
    {
        return in_array($method, [
            'relation',
            'equals',
            'like',
            'notEquals',
            'greaterThan',
            'greaterThanOrEquals',
            'lessThan',
            'lessThanOrEquals',
            'between',
            'notBetween',
            'null',
            'notNull',
        ]) || in_array($method, array_keys(static::$macros));
    }

    public function apply(Builder $query): Builder
    {
        $relations = collect(array_keys($this->relations));

        foreach ($this->filters as $column => $value) {
            $foundRelation = false;
            foreach ($relations as $relation) {
                if (Str::startsWith(Str::snake($column), $relation)) {
                    $suffix = Str::after($column, Str::camel($relation));

                    if ($this->methodExists('relation' . $suffix)) {
                        $this->{'relation' . $suffix}($query, $relation, $value);
                        $foundRelation = true;
                        break;
                    }
                }
            }

            if ($foundRelation) {
                continue;
            }

            foreach ($this->modelInfo->attributes as $attribute) {
                if (Str::startsWith(Str::snake($column), $attribute->name)) {
                    $suffix = Str::after($column, Str::camel($attribute->name));

                    $suffix == '' && $suffix = 'Equals';

                    if (!$this->methodExists(Str::camel($suffix))) {
                        continue;
                    }

                    $this->{Str::camel($suffix)}($query, $attribute->name, $value);

                    break;
                }
            }


        }
        return $query;
    }

}

