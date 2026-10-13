# Customization

Validation, API-only events, controller lifecycle hooks and M:N relation sync.

**Neighbours:** who may reach the action at all -> `authorization.md` · what the endpoint returns
afterwards -> `routes-and-contract.md`.

## Validation

```php
use Luminix\Backend\Validation;

#[Validation\WithValidator(PostValidator::class)]
class Post extends Model { use LuminixModel; }

class PostValidator extends Validation\Validator {
    public function store(): array
    {
        return ['title' => 'required|string|max:255'];
    }
    public function update(Post $post)
    {
       return ['title' => 'sometimes|string|max:255'];
    }
}
```

## API-specific events

Luminix fires its own events, triggering only during API calls -> target API-initiated changes
without affecting other code paths:

```
luminixCreating / luminixCreated
luminixUpdating / luminixUpdated
luminixDeleting / luminixDeleted
luminixRestoring / luminixRestored
luminixSaving   / luminixSaved
```

Observe them like any Eloquent event — an observer method of the same name (example under
*Attribute fill protection* in `authorization.md`).

## Lifecycle hooks (extend `ResourceController`)

```php
#[WithController(PostController::class)]
class Post extends Model { use LuminixModel; }

class PostController extends ResourceController {
    protected function afterCreate(Model $model, Request $request): void {
        // runs after every POST create, inside DB transaction
    }
}
```

Available hooks: `beforeSave`, `afterSave`, `beforeCreate`, `afterCreate`, `beforeUpdate`,
`afterUpdate`, `beforeDelete`, `afterDelete`, `beforeRestore`, `afterRestore`,
`onTransactionError`.

Where they sit in a write request:

```
Request → Middleware → Gate check → ResourceController
                                          ↓
                               beforeSave / beforeCreate
                                          ↓
                               Model::create() inside transaction
                                          ↓
                               afterCreate → afterSave
                                          ↓
                               Luminix event → Response
```

Every write action runs inside a DB transaction. Throw from any hook -> rolls back cleanly.

## Relationship sync

Declare syncable relations:

```php
protected array $syncs = ['tags', 'roles'];

public function roles(): BelongsToMany
{
    return $this->belongsToMany(Role::class)
        ->withPivot('expires_at')   // pivot columns the client may write
        ->withTimestamps();         // set by Eloquent, never taken from the client
}
```

Enables the M:N routes (`sync`/`attach`/`detach` -> `routes-and-contract.md`). `sync` takes a JSON
array of related keys and/or rows keyed by the RELATED model's primary key:
`[1, 2, { "id": 3, "expires_at": "2025-12-31" }]`. `attach` takes the pivot data as its body.

Pivot data keeps only the `withPivot()` columns; any other key is dropped without an error, like a
non-`$fillable` attribute on `update`. The pivot keys, the morph type and the `withTimestamps()`
columns always come from Eloquent. A `withPivot()` column is both loaded and client-writable — to
keep one server-controlled, or to set a column from the request, override `pivotAttributes()` in
the model's controller:

```php
class UserController extends ResourceController {
    protected function pivotAttributes(BelongsToMany $relation, array $attributes): array {
        $attributes = parent::pivotAttributes($relation, $attributes);

        if ($relation->getTable() === 'role_user') {
            $attributes['granted_by'] = auth()->id();  // replaces any client value
        }

        return $attributes;
    }
}
```

> Required for `BelongsToMany.sync()` from `@luminix/core`.
