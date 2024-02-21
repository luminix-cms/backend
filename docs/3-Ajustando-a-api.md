
## Ajustando a API

O Luminix Backend é altamente configurável e permite que você ajuste a API de acordo com suas necessidades. Nesta seção, vamos ver como modificar as rotas, validar os dados, personalizar a saída dos dados, criar abas, realizar buscas, aplicar filtros e ordenação, fazer Eager Loading, importar e exportar dados e criar um controlador específico para um modelo.

### Modificando rotas

Por padrão, o Luminix Backend cria as rotas da API com o prefixo `luminix-api`. Se você deseja alterar esse prefixo, basta adicionar a chave `api.prefix` no arquivo de configuração `config/luminix/backend.php`:

```php
'api' => [
    'prefix' => 'api',
],
```

Dentro deste prefixo, as rotas são criadas para cada modelo. As rotas são geradas pelo método estático `getLuminixRoutes`, que pode ser sobrescrito no modelo para personalizar as rotas. Por exemplo, se você deseja criar uma rota adicional para um modelo, adicione o método `getLuminixRoutes` no modelo:

```php
use Luminix\Backend\Model\LuminixModel;

class Post extends Model
{
    use LuminixModel;

    static function getLuminixRoutes(): array
    {
        return self::mergeDefaultRoutes([
            'customAction' => [
                'method' => 'post',
                'url' => 'posts/{id}/custom',
            ],
        ]);
    }
}
```

O método `mergeDefaultRoutes` é utilizado para mesclar as rotas padrão com as rotas personalizadas. As rotas padrão são:

- `index`: Lista todos os registros
- `store`: Cria um novo registro
- `show`: Exibe um registro
- `update`: Atualiza um registro
- `destroy`: Deleta um registro
- `destroyMany`: Deleta vários registros
- `restoreMany`: Restaura vários registros
- `import`: Importa registros (planejado)
- `export`: Exporta registros (planejado)

Para assinalar um método para a rota criada, é necessário que o controlador da API seja sobrescrito e que exista um método com mesmo nome da ação declarado (ex: `customAction`). Veremos como fazer isso na seção [Criando um controlador](#Criando-um-controlador).

Se o método da ação for 'get', a ação pode ser declarada apenas com uma string indicando a URL. Se o método for 'post', 'put' ou 'delete', a ação deve ser declarada como um array com as chaves `method` e `url`.

```php
'customAction' => 'posts/{id}/custom', // Método 'get'
'customAction' => [ // Método 'post'
    'method' => 'post',
    'url' => 'posts/{id}/custom',
],
```

### Validação de dados

O Luminix Backend utiliza o Laravel para validar os dados. Para personalizar a validação, adicione o método `getValidationRules(string $for)` no modelo:

```php

use Luminix\Backend\Model\LuminixModel;

class Post extends Model
{
    use LuminixModel;

    public function getValidationRules(string $for): array
    {
        if ($for === 'store') {
            return [
                'title' => 'required|string|max:255',
                'content' => 'required|string',
            ];
        }
        // update
        return [
            'title' => 'string|max:255',
            'content' => 'string',
        ];
    }
}
```

Caso você queira ter controle total sobre a validação, é possível sobrescrever o método `validateRequest` no modelo:

```php
use Luminix\Backend\Model\LuminixModel;

class Post extends Model
{
    use LuminixModel;

    public function validateRequest(Request $request, string $for)
    {
        if ($for === 'store') {
            Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'content' => 'required|string',
            ])->validate();
            return;
        }
        // update
        $request->validate([
            'title' => 'string|max:255',
            'content' => 'string',
        ]);
    }
}
```

### Saída dos dados

O Luminix Backend utiliza o Laravel para serializar os dados. Existem várias estratégias para personalizar a saída dos dados.

 - Defina o atributo `$hidden` no modelo para ocultar campos
 - Defina o atributo `$appends` no modelo para adicionar campos computados
 - Faça [pré carregamento](#Eager-Loading) de relacionamentos
 - [Criar um recurso](#Recursos) para personalizar a saída dos dados

### Abas

O Luminix Backend permite que você crie "abas" para agrupar os modelos em diferentes seções. Por padrão, os modelos possuem uma aba "all" e se o modelo possuir `SoftDeletes`, uma aba "trashed". Para consultar dados de uma aba, basta passar o parâmetro `tab` na URL da requisição de listagem:

```json
// GET /luminix-api/posts?tab=trashed
{
    "data": [
        {
            "id": 1,
            "title": "Post 1",
            "deleted_at": "2021-01-01 00:00:00",
        }
    ]
}
```

Para manipular as abas, sobrescreva o método `scopeWhereBelongsToTab` no modelo:

```php
use Luminix\Backend\Model\LuminixModel;

class Post extends Model
{
    use LuminixModel;

    public function scopeWhereBelongsToTab(Builder $query, string $tab)
    {
        if ($tab === 'trashed') {
            $query->onlyTrashed();
        }
        if ($tab === 'drafts') {
            $query->where('status', 'draft');
        }
        if ($tab === 'published') {
            $query->where('status', 'published');
        }
    }
}
```

### Busca

Para buscar registros, basta passar o parâmetro `q` na URL da requisição de listagem:

```json
// GET /luminix-api/posts?q=Post
{
    "data": [
        {
            "id": 1,
            "title": "Post 1",
        },
        {
            "id": 2,
            "title": "Post 2",
        }
    ]
}
```

O Luminix Backend possui uma implementação rudimentar de busca, que utiliza os campos listados em `$fillable` encontrar registros. Para personalizar a busca, sobrescreva o método `scopeSearch` no modelo:

```php
use Luminix\Backend\Model\LuminixModel;

class Post extends Model
{
    use LuminixModel;

    public function scopeSearch(Builder $query, string $search)
    {
        $query->where('title', 'like', "%$search%");
        $query->orWhere('content', 'like', "%$search%");
        $query->orWhereHas('author', function ($query) use ($search) {
                // se User for um LuminixModel:
                $query->search($search);
            });
    }
}
```

### Filtros e ordenação

O Luminix Backend permite que você filtre e ordene os registros. A ordenação é feita passando o parâmetro `order_by` na URL, no formato `campo:ordem`. A ordem pode ser `asc` ou `desc`. Por exemplo:

```json
// GET /luminix-api/posts?order_by=title:asc
{
    "data": [
        {
            "id": 2,
            "title": "A",
        },
        {
            "id": 1,
            "title": "B",
        }
    ]
}
```

Usando a implementação padrão de ordenação, uma exceção será gerada caso seja fornecido um campo que não existe na tabela do modelo. Para personalizar a ordenação, sobrescreva o método `scopeApplyOrderBy` no modelo:

```php
use Luminix\Backend\Model\LuminixModel;

class Post extends Model
{
    use LuminixModel;

    public function scopeApplyOrderBy(Builder $query, string $field, string $direction)
    {
        if ($field === 'title') {
            $query->orderBy('title', $direction);
        }
        if ($field === 'author') {
            $query->orderBy(
                User::select('name')
                    ->whereColumn('users.id', 'posts.user_id')
                    ->limit(1),
                $direction
            );
        }
    }
}
```

A criação de filtros deve ser implementada pelo desenvolvedor, oferecendo uma forma flexível de filtrar os registros. Nas requisições, os filtros são passados como uma string JSON no parâmetro `filters`. Por exemplo:

```json
// GET /luminix-api/posts?filters={"tags":[1,2]}
{
    "data": [
        {
            "id": 1,
            "title": "Post 1",
        },
        {
            "id": 2,
            "title": "Post 2",
        }
    ]
}
```

Para que os filtros funcionem, é necessário sobrescrever o método `scopeWhereMatchesFilter` no modelo:

```php
use Luminix\Backend\Model\LuminixModel;

class Post extends Model
{
    use LuminixModel;

    public function scopeWhereMatchesFilter(Builder $query, array $filters)
    {
        if (isset($filters['tags'])) {
            $query->whereHas('tags', function ($query) use ($filters) {
                $query->whereIn('id', $filters['tags']);
            });
        }
    }
}
```

### Eager Loading

Você pode usar os métodos `scopeBeforeLuminix` ou `scopeAfterLuminix` para realizar o carregamento antecipado de relacionamentos. Por exemplo, para carregar antecipadamente os relacionamentos `tags` e `author`:

```php
use Luminix\Backend\Model\LuminixModel;

class Post extends Model
{
    use LuminixModel;

    public function scopeBeforeLuminix(Builder $query, Request $request)
    {
        return $query->with('tags', 'author');
    }
}
```

Recomendamos no entanto, que sejam utilizados relacionamentos com limitações de carga antecipada, para evitar a carga antecipada de muitos registros. Para que isso seja possível, o Luminix Backend utiliza o pacote [Eloquent Eager Limit](https://github.com/staudenmeir/eloquent-eager-limit).

Um exemplo de implementação que leva em conta a limitação de carga antecipada, e só aplica o carregamento para relacionamentos selecionados:

```php
use Luminix\Backend\Model\LuminixModel;

class User extends Model
{
    use LuminixModel;

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class);
    }

    public function scopeBeforeLuminix(Builder $query, Request $request)
    {
        // ?with=posts,tasks
        if ($request->has('with')) {
            $with = explode(',', $request->query('with'));
            
            if (in_array('posts', $with)) {
                $query->with([ 'posts' => fn ($query) => $query->latest()->limit(5) ]);
            }
            if (in_array('tasks', $with)) {
                $query->with([ 'tasks' => fn ($query) => $query->limit(5) ]);
            }
        }
    }
}
```

### Importação e exportação

Planejado

### Criando um controlador

O Luminix Backend permite que você crie um controlador específico para um modelo. Para isso, adicione a chave `api.controller_overrides` no arquivo de configuração `config/luminix/backend.php`:

```php
'api' => [
    'controller_overrides' => [
        'App\Models\Post' => 'App\Http\Controllers\PostController',
    ],
],
```

Neste caso, os endpoints relacionados ao modelo `Post` serão tratados pelo controlador `App\Http\Controllers\PostController`. O controlador sobrescrito deve preferencialmente estender a classe `Luminix\Backend\Controllers\ResourceController` e implementar os métodos necessários. Por exemplo:

```php
namespace App\Http\Controllers;

use Luminix\Backend\Controllers\ResourceController;

class PostController extends ResourceController
{
    // ...
}
```

O controlador sobrescrito pode alterar o comportamento de qualquer endpoint da API, sobrescrevendo os métodos correspondentes ao endpoint. Este método também pode ser usado para criar novos métodos para determinados modelos. Por exemplo, para sobrescrever o método `store`:

```php
namespace App\Http\Controllers;

use Luminix\Backend\Controllers\ResourceController;

class PostController extends ResourceController
{
    public function store(Request $request)
    {
        // ...
    }
}
```

Neste caso ficará a cargo do desenvolvedor realizar toda a implementação, inclusive de segurança e validação dos dados.

Existem alguns métodos que são úteis para personalizar internamente o controlador, sem ter que refazer a implementação completa dos métodos:

| Método | Descrição |
| --- | --- |
| `beforeSave(Request $request, $item)` | Chamado antes de salvar um registro |
| `afterSave(Request $request, $item)` | Chamado após salvar um registro |

### Recursos

O Luminix Backend permite que você crie um [recurso](https://laravel.com/docs/10.x/eloquent-resources) para personalizar a saída dos dados. Para que um recurso seja aplicado, basta que ele seja criado em `app/Http/Resources` e que ele seja criado com o nome `{$Model}Resource`, onde `{$Model}` é o nome do modelo. Por exemplo, para criar um recurso para o modelo `Post`, crie o arquivo `app/Http/Resources/PostResource.php`:

O Luminix irá automaticamente aplicar o recurso ao retornar os dados do modelo. Para mais detalhes sobre como criar um recurso, consulte a [documentação do Laravel](https://laravel.com/docs/10.x/eloquent-resources).

