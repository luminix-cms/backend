# Luminix Backend

Pacote Laravel para geração automática de endpoints RESTful a partir de modelos Eloquent, com filtragem avançada, controle de acesso por Gates e gerenciamento de relacionamentos.

## Requisitos

- PHP 8.2+
- Laravel 11+

## Instalação

```bash
composer require luminix/backend
```

Publique o arquivo de configuração:

```bash
php artisan vendor:publish --tag=luminix-config
```

## Uso Básico

Adicione a trait `LuminixModel` a qualquer modelo para expô-lo via API:

```php
use Illuminate\Database\Eloquent\Model;
use Luminix\Backend\Model\LuminixModel;

class Post extends Model
{
    use LuminixModel;

    protected $fillable = ['title', 'body'];
}
```

Isso gera automaticamente os seguintes endpoints sob o prefixo `/luminix-api`:

| Método | URL | Ação |
|--------|-----|------|
| `GET` | `/luminix-api/posts` | Listagem paginada |
| `POST` | `/luminix-api/posts` | Criação |
| `GET` | `/luminix-api/posts/{id}` | Busca por ID |
| `POST` | `/luminix-api/posts/{id}` | Atualização |
| `DELETE` | `/luminix-api/posts/{id}` | Exclusão |
| `DELETE` | `/luminix-api/posts` | Exclusão em lote |
| `POST` | `/luminix-api/posts/restore` | Restauração em lote (SoftDeletes) |

## Funcionalidades

**Filtragem e Busca**
- 15+ operadores de filtro (`equals`, `contains`, `between`, `notBetween`, `greaterThan`, `null`, `relation`, etc.) via parâmetro `where`
- Busca textual via parâmetro `q` (busca em todos os campos `fillable`)
- Ordenação via `order_by`, paginação via `page`/`per_page`
- Filtragem por abas (`tab`) para conjuntos pré-definidos (ex: `?tab=trashed`)
- Operadores personalizados via macros em `ModelFilter`

**Segurança**
- Middleware configurável globalmente ou por endpoint
- Integração com Laravel Gates (filosofia *deny first*)
- Segurança em nível de linha via `scopeAllowed` — chamado automaticamente em toda consulta

**Relacionamentos**
- Sync, attach e detach para relações `BelongsToMany` e `MorphToMany`
- Suporte a dados de pivot

**Customização**
- Controlador por modelo via atributo `#[WithController]`
- Hooks no ciclo de vida: `beforeSave`, `afterCreate`, `beforeDelete`, `onTransactionError`, etc.
- Validação inline (`getValidationRules`) ou em classes dedicadas (`#[WithValidator]`)
- Respostas customizadas via Laravel Resources (`#[WithResource]`)
- 10 eventos Luminix-específicos (`luminixCreating`, `luminixSaved`, etc.) disparados apenas em operações de API

## Exemplo com Segurança

```php
// app/Providers/AppServiceProvider.php
Gate::define('read-post', fn (?User $user) => true);          // leitura pública
Gate::define('create-post', fn (?User $user) => !!$user);     // apenas autenticados
Gate::define('update-post', fn (User $user, Post $post) => $user->id === $post->user_id);
Gate::define('delete-post', fn (User $user, Post $post) => $user->id === $post->user_id);
```

```php
// app/Models/Post.php
public function scopeAllowed(Builder $query, string $permission): void
{
    if (in_array($permission, ['update', 'delete'])) {
        $query->where('user_id', auth()->id());
    }
}
```

## Documentação

Consulte o [índice completo da documentação](docs/README.md) para referência detalhada de todos os recursos.

## Licença

[MIT](https://opensource.org/licenses/MIT)
