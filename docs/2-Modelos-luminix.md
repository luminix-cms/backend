## Modelos Luminix

Os modelos Luminix, baseados no Laravel, são enriquecidos por meio da adoção do trait `LuminixModel` ou pela implementação da interface `LuminixModelInterface`. Esta abordagem permite funcionalidades adicionais em comparação aos modelos padrão do Laravel.

### Integração Automatizada com Luminix Backend

Quando identificados pelo Luminix Backend, os modelos Luminix automaticamente ganham endpoints de API correspondentes. Este processo inclui a identificação e a integração de relações do modelo, facilitando operações através da API.

#### Exemplo Prático: Modelo `Post`

```php
use Luminix\Backend\Model\LuminixModel;

class Post extends Model
{
    use LuminixModel;

    protected $fillable = ['title', 'content'];
}
```

Com a simples declaração acima, o Luminix Backend cria um conjunto completo de CRUD (Create, Read, Update, Delete) para o modelo `Post`, com os seguintes endpoints:

| URL                  | Rota                 | Método | Descrição                |
|----------------------|----------------------|--------|--------------------------|
| /luminix-api/posts   | luminix.post.index   | GET    | Listar os posts          |
| /luminix-api/posts   | luminix.post.store   | POST   | Criar um novo post       |
| /luminix-api/posts/{id} | luminix.post.show | GET    | Exibir um post           |
| /luminix-api/posts/{id} | luminix.post.update | PUT    | Atualizar um post        |
| /luminix-api/posts/{id} | luminix.post.destroy | DELETE | Deletar um post         |
| /luminix-api/posts   | luminix.post.destroyMany | DELETE | Deletar vários posts   |
| /luminix-api/posts   | luminix.post.restoreMany | PUT    | Restaurar vários posts  |

### Definição de Campos Preenchíveis

A especificação dos campos preenchíveis na model, utilizando `$fillable`, é crucial para o funcionamento adequado do Luminix Backend, impactando diretamente nos métodos `create` e `update` do controlador.

#### Exemplo com o Modelo `User`

```php
use Luminix\Backend\Model\LuminixModel;

class User extends Model
{
    use LuminixModel;

    protected $fillable = ['name', 'email', 'password'];
}
```

### Gestão de Relacionamentos

A detecção de relações pelo Luminix Backend requer que elas sejam declaradas com tipos de retorno explícitos, permitindo ao Backend processar os dados de maneira adequada.

#### Exemplo de Relacionamentos no Modelo `Post`

```php
use Luminix\Backend\Model\LuminixModel;

class Post extends Model
{
    use LuminixModel;

    protected $fillable = ['name', 'category_id'];

    protected $syncs = ['tags'];

                        //: TipoObrigatório
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

                            //: TipoObrigatório
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
```

Exemplo de requisição com relacionamentos inclusos:

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

 > Esta operação irá sincronizar as tags do post, removendo as tags que eventualmente não estiverem presentes na requisição.

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

