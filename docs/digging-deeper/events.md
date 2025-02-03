# Events

Luminix provides a powerful way to customize the behavior of the generated Rest API by using **events**. Events allow you to hook into the Rest API lifecycle and execute custom logic at specific points.

## Eloquent Event Extensions

You can use Eloquent events to hook into the lifecycle of a model. For example, the `creating` event allows you to execute custom logic before a model is created.

```php
class Article extends Model 
{
    protected static function booted() 
    {
        static::creating(function ($article) {
            $article->slug = Str::slug($article->title);
        });
    }
}
```

However, if you want to apply this logic **only when the model is being created through the Rest API**, you can use Luminix-specific events. Instead of listening to the Eloquent `creating` event, you can listen to the `luminixCreating` event.

```php
class Article extends Model 
{
    protected static function booted() 
    {
        static::luminixCreating(function ($article) {
            $article->slug = Str::slug($article->title);
        });
    }
}
```

Luminix provides corresponding events for most default Eloquent events. These events are triggered only when the model is being created, updated, deleted, restored, etc., through the Rest API.

| Eloquent Event | Luminix Event     |
|----------------|-------------------|
| `creating`     | `luminixCreating` |
| `created`      | `luminixCreated`  |
| `updating`     | `luminixUpdating` |
| `updated`      | `luminixUpdated`  |
| `deleting`     | `luminixDeleting` |
| `deleted`      | `luminixDeleted`  |
| `restoring`    | `luminixRestoring`|
| `restored`     | `luminixRestored` |
| `saving`       | `luminixSaving`   |
| `saved`        | `luminixSaved`    |

---

## Observers

If you prefer to encapsulate your event logic in a separate class, you can use **observers**. Observers allow you to group related event logic together.

```php
class ArticleObserver 
{
    public function luminixCreating($article) 
    {
        $article->slug = Str::slug($article->title);
    }

    public function luminixDeleting($article) 
    {
        $article->comments()->delete();
    }
}
```

To associate the observer with the `Article` model, use the `#[ObservedBy]` attribute:

```php
#[ObservedBy(ArticleObserver::class)]
class Article extends Model 
{
    // Model logic here
}
```

---

## Listeners

For more advanced use cases, you can use **listeners** to handle Luminix events. Listeners are particularly useful when you want to decouple your event handling logic from your models or observers.

To create a listener, use the `php artisan make:listener` command:

```bash
php artisan make:listener ArticleCreatingListener --event="Luminix\\Backend\\Events\\CreatingResource"
```

Since the `CreatingResource` event is dispatched for all resources, you need to check if the event is for the `Article` model:

```php
namespace App\Listeners;

use Luminix\Backend\Events\CreatingResource;
use Illuminate\Contracts\Queue\ShouldQueue;

class ArticleCreatingListener implements ShouldQueue 
{
    public function handle(CreatingResource $event) 
    {
        if ($event->model instanceof \App\Models\Article) {
            $event->model->slug = Str::slug($event->model->title);
        }
    }
}
```

---

## Custom Events

If you want to dispatch custom events for specific models, you can define them in the `$dispatchesEvents` property of the model. This allows you to create more granular and model-specific events.

```php
class Article extends Model 
{
    protected $dispatchesEvents = [
        'luminixCreating' => CreatingArticleEvent::class,
    ];
}
```

Define the custom event class:

```php
use App\Models\Article;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class CreatingArticleEvent 
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Article $model
    ) {}
}
```

Then, create a listener for the custom event:

```php
class CreatingArticleListener 
{
    public function handle(CreatingArticleEvent $event) 
    {
        $event->model->slug = Str::slug($event->model->title);
    }
}
```

By using custom events, you can ensure that your event handling logic is specific to the `Article` model and not triggered for other resources.

---

## Summary

- Use **Eloquent events** for general model lifecycle hooks.
- Use **Luminix events** to apply logic only when interacting with the Rest API.
- Use **observers** to group related event logic.
- Use **listeners** for advanced, decoupled event handling.
- Use **custom events** for model-specific event handling.

This approach provides flexibility and ensures your code remains clean and maintainable.
