
## Modelos Luminix

Os modelos Luminix são modelos que utilizam o trait `LuminixModel` ou implementam a interface `LuminixModelInterface`. Eles possuem algumas funcionalidades a mais que os modelos comuns do Laravel.

Quando um modelo Luminix é descoberto pelo Luminix Backend, serão gerados os endpoints da API para ele. Além disso, o Luminix Backend irá detectar os relacionamentos do modelo e permitir que eles sejam preenchidos através da API.

```php
use Luminix\Backend\Model\LuminixModel;

class Post extends Model
{
    use LuminixModel;

    protected $fillable = [
        'title',
        'content',
    ];
}
```

Isso é tudo que é necessário para criar um CRUD completo para a model `Post`. O Luminix Backend irá criar os seguintes endpoints:

| URL | Rota | Método | Descrição |
| --- | ---- | ------ | --------- |
| /luminix-api/posts | luminix.post.index | GET | Listar os posts |
| /luminix-api/posts | luminix.post.store | POST | Criar um novo post |
| /luminix-api/posts/{id} | luminix.post.show | GET | Exibir um post |
| /luminix-api/posts/{id} | luminix.post.update | PUT | Atualizar um post |
| /luminix-api/posts/{id} | luminix.post.destroy | DELETE | Deletar um post |
| /luminix-api/posts | luminix.post.destroyMany | DELETE | Deletar vários posts |
| /luminix-api/posts | luminix.post.restoreMany | PUT | Restaurar vários posts |

### Campos preenchíveis

A configuração do atributo `$fillable` na model é essencial para o funcionamento do Luminix Backend. Ele é utilizado para definir quais campos podem ser preenchidos nos métodos `create` e `update` do controlador.

```php
use Luminix\Backend\Model\LuminixModel;

class User extends Model
{
    use LuminixModel;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];
}
```

### Relacionamentos

Para que os relacionamentos sejam detectados pelo Luminix Backend, é necessário que eles sejam declarados com tipos de retorno. Isso é necessário para que o Luminix Backend saiba como tratar os dados.

```php
use Luminix\Backend\Model\LuminixModel;

class Post extends Model
{
    use LuminixModel;

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
```

Com isso será possível fazer requisições que incluam os relacionamentos, como no exemplo abaixo:

```json
// POST /luminix-api/posts
{
    "name": "Post 1",
    "category": {
        "id": 1
    },
    "tags": [
        {
            "id": 1
        },
        {
            "id": 2
        }
    ],
}
```

#### `BelongsTo`

```php
use Luminix\Backend\Model\LuminixModel;

class Post extends Model
{
    use LuminixModel;

    protected $fillable = [
        'name',
        'category_id',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
```

Relações `BelongsTo` poderão ser preenchidas se a chave estrangeira estiver listada em `$fillable`. Então, o Luminix Backend espera que o relacionamento seja passado como um objeto com a sua chave primária.

```json
// POST /luminix-api/posts
{
    "name": "Post 1",
    "category": {
        "id": 1
    }
}
```

 > Também é possível passar diretamente o atributo `category_id` no corpo da requisição.

#### `BelongsToMany`

```php
use Luminix\Backend\Model\LuminixModel;

class Post extends Model
{
    use LuminixModel;

    protected $syncs = [
        'tags',
    ];

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }
}
```

Em relações `BelongsToMany`, é necessário que o atributo `$syncs` esteja definido e contenha o nome do relacionamento a ser sincronizado. Então, o Luminix Backend espera que o relacionamento seja passado como um array de objetos com a chave primária. Esta requisição criaria um post com as tags de id 1 e 2:

```json
// POST /luminix-api/posts
{
    "name": "Post 1",
    "tags": [
        {
            "id": 1,
        },
        {
            "id": 2,
        }
    ],
}
```

Cada elemento dentro do array também pode conter um atributo `pivot` para definir os atributos da tabela pivô.

```json
// POST /luminix-api/posts
{
    "name": "Post 1",
    "tags": [
        {
            "id": 1,
            "pivot": {
                "order": 1
            }
        },
        {
            "id": 2,
            "pivot": {
                "order": 2
            }
        }
    ],
}
```


#### `MorphTo`
    
```php
use Luminix\Backend\Model\LuminixModel;

class Image extends Model
{
    use LuminixModel;

    protected $fillable = [
        'url',
        'imageable_id',
        'imageable_type',
    ];

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }
}
```

Relações `MorphTo` podem ser preenchidas se os campos `imageable_id` e `imageable_type` estiverem listados em `$fillable`. Então, o Luminix Backend espera que o relacionamento seja passado como um objeto com a sua chave primária. O atributo `imageable_type` deve conter o nome da classe do modelo relacionado.

```json
// POST /luminix-api/images
{
    "url": "https://example.com/image.jpg",
    "imageable": {
        "id": 1
    },
    "imageable_type": "App\\Models\\Post"
}
```


#### `MorphToMany`

A relação `MorphToMany` estende a relação `BelongsToMany`, então seu comportamento é similar.

```php
use Luminix\Backend\Model\LuminixModel;

class Post extends Model
{
    use LuminixModel;

    protected $syncs = [
        'tags',
    ];

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }
}
```

A requisição para criar um post com tags seria:

```json
// POST /luminix-api/posts
{
    "name": "Post 1",
    "tags": [
        {
            "id": 1,
        },
        {
            "id": 2,
        }
    ],
}
```

