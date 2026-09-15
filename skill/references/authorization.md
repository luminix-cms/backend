# Authorization — deny first

Every action is blocked until a Gate explicitly allows it. Two independent layers: Gates answer
"may this user perform this action?", `scopeAllowed()` answers "which rows can this user reach?".

**Neighbours:** which status code each denial produces and in which order the checks run ->
`routes-and-contract.md` · `security.permissions` mapping -> `configuration.md` · setting
server-owned attributes -> `customization.md`.

## Gate protection

Gates follow the pattern **`{action}-{alias}`**:

```php
// AppServiceProvider::boot()
Gate::define('read-post',   fn(?User $user, Post $post) => true);                        // public
Gate::define('create-post', fn(?User $user)             => $user !== null);              // auth only
Gate::define('update-post', fn(?User $user, Post $post) => $user->id === $post->user_id);
Gate::define('delete-post', fn(?User $user, Post $post) => $user->id === $post->user_id);
```

Alias defaults to snake_case model name (`Post` → `post`, `ToDo` → `to_do`).

## Row-level protection — `scopeAllowed()`

Implement `scopeAllowed()` to answer one question: "which rows can this user reach for this
operation?":

```php
public function scopeAllowed(Builder $query, string $permission): void {
    if (in_array($permission, ['update', 'delete'])) {
        $query->where('user_id', auth()->id());
    }
}
```

It is called automatically on the generated queries whenever the action has a permission mapped in
`security.permissions` — never call it by hand.

**Action per endpoint** — the default `security.permissions` map, feeding both the gate name
`{action}-{alias}` and the `$permission` string `scopeAllowed()` receives:

| Endpoint | `{action}` | `scopeAllowed()` applied to |
|---|---|---|
| `index` / `show` | `read` | listing / item lookup |
| `store` | `create` | — (no pre-insert lookup, so `scopeAllowed` never runs) |
| `update` | `update` | pre-write lookup only |
| `destroy` / `destroyMany` | `delete` | pre-write lookup |
| `restoreMany` | `update` | pre-write lookup |
| `sync` / `attach` / `detach` | `update` | PARENT lookup only |

Custom `security.permissions` strings pass through to gate name and `scopeAllowed()` as-is. With
the default map `scopeAllowed()` only ever receives `read`, `update` or `delete` — `create` never
reaches it.

### What `scopeAllowed()` must not do

It is a row filter. Anything that cannot be expressed as a `WHERE` over the model's own rows
belongs to another mechanism:

- **Authorize creation.** Never consulted on `store` — the row does not exist yet. Use the
  `create-{alias}` Gate.
- **Validate request content.** Restricting which values a field may take (e.g. stopping `user_id`
  from pointing at another user) is validation or Gate work -> `customization.md`.
- **Control the response of a write.** The post-write refetch ignores the scope by contract.
- **Filter attributes or relations.** It filters rows of its own model. Hide attributes with
  `$hidden`/Resources.

## Attribute fill protection

Every `$fillable` column accepts whatever the client sends. Constrain it with a validator
(-> `customization.md`), and set trusted columns yourself by direct assignment inside an API-event
observer or a controller hook:

```php
class UserObserver
{
    public function luminixSaving(User $user) {
        if (auth()->user()->is_admin && request()->has('role_id')) { // only admins can set role_id
            // use direct attribute assignment
            $user->role_id = request()->json('role_id');
        }
    }
}
```

## Attribute searching protection

`where[]` filtering excludes the model's `$hidden` columns automatically. Edge cases: list the
model's filter-excluded attributes in `config/luminix/backend.php`:

```php
return [
    'api' => [
        'filter' => [
            'exclude' => [
                // add records here: {Full\ClassName}:{column1},{column2}...
                'App\Models\Post:author_notes', // this entry REPLACES the default `$hidden` exclusion for the model
                                                // -> repeat every `$hidden` column that must stay unfilterable
            ],
        ],
    ]
];
```
