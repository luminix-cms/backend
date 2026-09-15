# Querying the index

All params below are combinable in a single request. Multiple `where[]` keys are joined with `AND`.

**Neighbours:** which rows the user may reach at all -> `authorization.md` · route names and status
codes -> `routes-and-contract.md` · `api.max_per_page` and filter switches -> `configuration.md`.

| Parameter               | Effect                                            |
|-------------------------|---------------------------------------------------|
| `?page=2&per_page=25`   | Pagination (`per_page` cap: `api.max_per_page`, default 150) |
| `?q=laravel`            | Search across all `$fillable` fields              |
| `?order_by=created_at:desc` | Sort                                          |
| `?where[title:contains]=api` | Filter with operator                        |
| `?where[status]=active` | Exact match                                       |
| `?tab=trashed`          | Show only soft-deleted records                     |
| `?minified=1`           | Return `{id, label}` only (for dropdowns)         |

**Filter operators:** `equals`, `notEquals`, `greaterThan`, `lessThan`, `between`, `null`,
`notNull`, `contains`, `startsWith`, `endsWith`, `relation`, + custom macros.

Which columns `where[]` may reach is a security question -> *Attribute Searching Protection* in
`authorization.md`.

## Custom selection — `scopeBeforeLuminix` / `scopeAfterLuminix`

Override either scope in the model -> add custom selection behavior to the generated index:

```php
public function scopeBeforeLuminix(Builder $query, Request $request) {
    if ($request->has('with') && $request->with === 'comments') { // enabling eager loading of comments from posts
        $query->with('comments');
    }
}
// GET /luminix-api/posts?with=comments will contain all comments from posts in a single request.
```

## Custom filter operators — `scopeWhereMatchesFilter`

```php
use Luminix\Backend\Services\ModelFilter;

public function scopeWhereMatchesFilter(Builder $query, array $filters)
{
    if (isset($filters['custom_field'])) {
        // ... modify $query accordingly, then:
        unset($filters['custom_field']); // prevent package from attempting to filter this column
    }
    // apply default package filters on top
    (new ModelFilter(static::class, $filters))->apply($query);
}
// GET /luminix-api/users?where[custom_field]=value will flow through implemented query
```

## Reuse the pipeline in a custom endpoint — `luminixQuery()`

Apply the `luminixQuery($request, ?string $permission)` scope to replicate the auto-API index
reachable-rows/filtering/ordering pipeline on a custom endpoint.

```php
public function export(Request $request)
{
    // ⚠️ luminixQuery applies rows/filters/order ONLY — the Gate check ({action}-{alias})
    // is yours: route middleware or Gate::authorize.
    $items = Task::query()
        ->luminixQuery($request, 'read')
        ->get();
    // frontend passes the same `where[...]`/`q`/`order_by` query string it
    // sends to luminix.task.index -> identical row set, no drift.
}
```
