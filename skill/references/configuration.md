# Configuration — `config/luminix/backend.php`

Publish it with `php artisan vendor:publish --tag=luminix-config`.

```php
'models'    => ['directory' => 'Models'],           // where to discover models
'api'       => [
    'prefix'      => 'luminix-api',
    'max_per_page' => 150,
    'filter'      => ['enable' => true, 'throw' => true],
],
'security'  => [
    'gates_enabled' => true,
    'middleware'    => ['api', 'auth'],
    'permissions'   => [ /* endpoint → {action}; defaults in the table in authorization.md */ ],
],
```

- `api.prefix` — every generated route sits under it -> `routes-and-contract.md`
- `api.max_per_page` — `?per_page=` above it is a `422`, not a silent clamp -> `querying.md`
- `api.filter.exclude` — which columns `where[]` may never reach -> `authorization.md`
- `security.middleware` — `['web', 'auth']` for session-cookie auth (Blade-served SPA), `['api', 'auth']`
  for token auth; package default is `['api', 'auth']`
- `security.permissions` — remaps endpoint → action, feeding both gate name and `scopeAllowed()`
