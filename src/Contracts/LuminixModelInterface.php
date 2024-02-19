<?php

namespace Luminix\Backend\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

interface LuminixModelInterface {

    public function getLabel(): string;

    public function getRelationships(): array;

    public function getSyncs(): array;

    public function scopeBeforeLuminix(Builder $query, Request $request);

    public function scopeAfterLuminix(Builder $query, Request $request);

    public function scopeAllowed(Builder $query, string $permission);

    public function scopeSearch(Builder $query, string $search);

    public function scopeWhereBelongsToTab(Builder $query, string $tab);

    public function scopeWhereMatchesFilter(Builder $query, array $filters);

    public function scopeApplyOrderBy(Builder $query, string $column, string $direction);

    public function scopeLuminixQuery(Builder $query, Request $request, ?string $permission);

    public function validateRequest(Request $request, string $for);

    static function getLuminixRoutes(): array;

    static function getAlias(): string;

}
