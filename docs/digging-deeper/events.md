# Eventos

O Luminix oferece uma maneira poderosa de personalizar o comportamento da API REST gerada através de **eventos**. Os eventos permitem que você conecte lógica personalizada em pontos específicos do ciclo de vida da API.

## Extensões de Eventos do Eloquent

Você pode usar eventos do Eloquent para interagir com o ciclo de vida do modelo. Por exemplo, o evento `creating` permite executar lógica personalizada antes da criação de um modelo.

```php
class Article extends Model 
{
    protected static function booted() 
    {
        static::creating(function ($article) {
            // Gera um slug baseado no título antes de criar o registro
            $article->slug = Str::slug($article->title);
        });
    }
}
```

Porém, se desejar aplicar essa lógica **apenas quando o modelo é criado através da API REST**, use os eventos específicos do Luminix. Em vez de ouvir o evento `creating` do Eloquent, utilize o evento `luminixCreating`.

```php
class Article extends Model 
{
    protected static function booted() 
    {
        static::luminixCreating(function ($article) {
            // Gera slug apenas para operações via API REST
            $article->slug = Str::slug($article->title);
        });
    }
}
```

O Luminix fornece eventos correspondentes para a maioria dos eventos padrão do Eloquent. Esses eventos são acionados **exclusivamente** durante operações realizadas pela API REST.

| Evento do Eloquent | Evento do Luminix     |
|---------------------|-----------------------|
| `creating`          | `luminixCreating`     |
| `created`           | `luminixCreated`      |
| `updating`          | `luminixUpdating`     |
| `updated`           | `luminixUpdated`      |
| `deleting`          | `luminixDeleting`     |
| `deleted`           | `luminixDeleted`      |
| `restoring`         | `luminixRestoring`    |
| `restored`          | `luminixRestored`     |
| `saving`            | `luminixSaving`       |
| `saved`             | `luminixSaved`        |

---

## Observadores (Observers)

Para organizar a lógica de eventos em classes separadas, você pode usar **observadores**. Eles permitem agrupar lógicas relacionadas de forma estruturada.

```php
class ArticleObserver 
{
    public function luminixCreating($article) 
    {
        // Define slug durante a criação via API
        $article->slug = Str::slug($article->title);
    }

    public function luminixDeleting($article) 
    {
        // Exclui comentários associados ao deletar o artigo
        $article->comments()->delete();
    }
}
```

Para vincular o observador ao modelo `Article`, utilize o atributo `#[ObservedBy]`:

```php
#[ObservedBy(ArticleObserver::class)]
class Article extends Model 
{
    // Lógica do modelo aqui
}
```

---

## Ouvintes (Listeners)

Para casos mais complexos, **ouvintes** permitem tratar eventos do Luminix de forma desacoplada. São ideais quando a lógica não deve estar diretamente no modelo ou observador.

Crie um ouvinte com o comando Artisan:

```bash
php artisan make:listener ArticleCreatingListener --event="Luminix\\Backend\\Events\\CreatingResource"
```

Como o evento `CreatingResource` é disparado para todos os recursos, verifique se o evento pertence ao modelo `Article`:

```php
namespace App\Listeners;

use Luminix\Backend\Events\CreatingResource;
use Illuminate\Contracts\Queue\ShouldQueue;

class ArticleCreatingListener implements ShouldQueue 
{
    public function handle(CreatingResource $event) 
    {
        // Aplica lógica apenas para o modelo Article
        if ($event->model instanceof \App\Models\Article) {
            $event->model->slug = Str::slug($event->model->title);
        }
    }
}
```

---

## Eventos Personalizados

Para eventos específicos de um modelo, defina-os na propriedade `$dispatchesEvents` do modelo. Isso permite maior granularidade.

```php
class Article extends Model 
{
    protected $dispatchesEvents = [
        'luminixCreating' => CreatingArticleEvent::class, // Evento personalizado
    ];
}
```

Defina a classe do evento personalizado:

```php
use App\Models\Article;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class CreatingArticleEvent 
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Article $model // Injeta o modelo no evento
    ) {}
}
```

Crie um ouvinte para o evento personalizado:

```php
class CreatingArticleListener 
{
    public function handle(CreatingArticleEvent $event) 
    {
        // Lógica específica para criação de Article
        $event->model->slug = Str::slug($event->model->title);
    }
}
```

Isso garante que a lógica seja executada apenas para o modelo `Article`.

---

## Resumo

- **Eventos do Eloquent**: Para hooks gerais no ciclo de vida do modelo.
- **Eventos do Luminix**: Para lógica exclusiva da API REST.
- **Observadores**: Agrupam lógicas de eventos relacionadas.
- **Ouvintes**: Desacoplam a lógica de eventos complexos.
- **Eventos Personalizados**: Permitem granularidade por modelo.

Essa abordagem oferece flexibilidade e mantém o código organizado e fácil de manter.