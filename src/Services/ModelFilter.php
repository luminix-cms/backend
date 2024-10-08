<?php

namespace Luminix\Backend\Services;

use Exception;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Str;
use Luminix\Backend\Exceptions\InvalidFilterException;
use Luminix\Backend\Facades\Finder;
use Spatie\ModelInfo\Attributes\Attribute;
use Spatie\ModelInfo\ModelInfo;

class ModelFilter {

    use Macroable;

    protected ?array $relations;
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
        if ($value === '*') {
            return $query->has($relation);
        }
        $relatedModelAlias = $this->relations[$relation]['model'];
        $relatedModel = Finder::toClass($relatedModelAlias);

        $instance = new $relatedModel();
        return $query->whereHas($relation, function ($query) use ($value, $instance) {
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

    public function contains(Builder $query, string $column, mixed $value): Builder
    {
        return $query->where($column, 'like', '%' . $value . '%');
    }

    public function startsWith(Builder $query, string $column, mixed $value): Builder
    {
        return $query->where($column, 'like', $value . '%');
    }

    public function endsWith(Builder $query, string $column, mixed $value): Builder
    {
        return $query->where($column, 'like', '%' . $value);
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

    public static function operators(): array
    {
        return [
            'equals',
            'notEquals',
            'like',
            'contains', 
            'startsWith', 
            'endsWith',
            'greaterThan',
            'greaterThanOrEquals',
            'lessThan',
            'lessThanOrEquals',
            'between',
            'notBetween',
            'null',
            'notNull',
            'relation',
        ] + array_keys(static::$macros);
    }

    private function methodExists(string $method): bool
    {
        return in_array($method, static::operators());
    }

    private function attributeExists(string $attribute): bool
    {
        return $this->getValidAttributes()->contains('name', $attribute);
    }

    private function getValidAttributes(): Collection
    {
        $excluded = collect(config('luminix.backend.api.filter.exclude', []));

        $entry = $excluded->first(function ($entry) {
            return Str::startsWith($entry, $this->model . ':');
        });

        if (!$entry) {
            return $this->modelInfo->attributes
                ->where('hidden', false)
                ->where('appended', false)
                ->pluck('name');
        }

        [, $columns] = explode(':', $entry);
        $excludedColumns = explode(',', $columns);

        return $this->modelInfo->attributes
            ->where('appended', false)
            ->pluck('name')
            ->filter(function ($attribute) use ($excludedColumns) {
                return !in_array($attribute, $excludedColumns);
            })
            ->values();

    }

    public function apply(Builder $query): Builder
    {
        $relations = collect(array_keys($this->relations ?? []));

        foreach ($this->filters as $columnOperator => $value) {
            
            if (Str::contains($columnOperator, ':')) {
                [$column, $operator] = explode(':', $columnOperator);
            } else {
                $column = $columnOperator;
            }
            
            $isRelation = $relations->contains(Str::snake($column));
            
            if (!isset($operator)) {
                $operator = $isRelation
                    ? 'relation'
                    : 'equals';
            }

            if ($isRelation) {
                $column = Str::snake($column);
            }

            if (!$this->methodExists($operator) || (!$isRelation && !$this->attributeExists($column))) {
                $this->handleInvalidFilter($column, $operator, $value);

                unset($operator);
                continue;
            }

            $this->{$operator}($query, $column, $value);

            unset($operator);
        }

        return $query;
    }

    private function handleInvalidFilter(string $column, string $operator, mixed $value): void
    {
        if (config('luminix.backend.api.filter.throw', true)) {
            throw new InvalidFilterException(
                '[Luminix] Invalid filter provided for model "' . $this->model . '"\n'
                . 'Column: ' . $column . '\n'
                . 'Operator: ' . $operator . '\n'
                . 'Value: ' . $value
            );
        } else {
            Log::warning('[Luminix] Invalid filter provided', [
                'model' => $this->model,
                'column' => $column,
                'operator' => $operator,
                'value' => $value,
            ]);
        }
    }

}

