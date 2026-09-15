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
```

Enables the M:N routes (`sync`/`attach`/`detach` -> `routes-and-contract.md`); `sync` takes a JSON
body of keys and/or pivot rows: `[1, 2, { 'tag_id': 3, ...pivotData }]`.

> Required for `BelongsToMany.sync()` from `@luminix/core`.
