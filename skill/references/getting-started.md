# Getting started

```bash
composer require luminix/backend
php artisan vendor:publish --tag=luminix-config   # optional — config/luminix/backend.php
```

## Minimum viable model

```php
use Illuminate\Database\Eloquent\Model;
use Luminix\Backend\Model\LuminixModel;

class Post extends Model {
    use LuminixModel;
    protected $fillable = ['title', 'body', 'published_at'];
}
```

That is the whole API: no controller, no route, no serializer. Discovery is automatic.

Four requirements, all silent failures when missed:

1. **Namespace** — the model must live where `models.directory` points (`App\Models` by default)
   -> `configuration.md`.
2. **The trait** — `LuminixModel` on the class.
3. **`$fillable`** — nothing is mass-assignable without it, so create and update arrive empty.
4. **Primary key** — models without one are not supported.

## Gates are mandatory

Deny-first: with no Gate defined, every endpoint answers `401`. The minimum to get a readable API:

```php
// AppServiceProvider::boot()
Gate::define('read-post',   fn(?User $user, Post $post) => true);
Gate::define('create-post', fn(?User $user)             => $user !== null);
Gate::define('update-post', fn(?User $user, Post $post) => $user->id === $post->user_id);
Gate::define('delete-post', fn(?User $user, Post $post) => $user->id === $post->user_id);
```

Gate name is `{action}-{alias}`, and the alias is the class name in snake_case. Override it with a
static `getAlias(): string` on the model — the route names follow it too.

Full authorization model, including row-level filtering -> `authorization.md`.
