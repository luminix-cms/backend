# Façade Finder

O Luminix expõe a façade `Luminix\Backend\Facades\Finder` para interagir com o serviço de descoberta de modelos (`ModelFinder`) em qualquer ponto da sua aplicação.

## Métodos Disponíveis

### `Finder::all()`

Retorna uma `Collection` com todos os modelos Luminix descobertos, indexada pelo apelido do modelo:

```php
use Luminix\Backend\Facades\Finder;

$models = Finder::all();
// Collection: ['user' => App\Models\User::class, 'to_do' => App\Models\ToDo::class, ...]
```

### `Finder::toAlias(string $modelClass)`

Converte o nome completo (FQCN) de um modelo para seu apelido:

```php
Finder::toAlias(App\Models\User::class);
// 'user'
```

### `Finder::toClass(string $alias)`

Converte um apelido para o nome completo (FQCN) do modelo:

```php
Finder::toClass('user');
// 'App\Models\User'
```

### `Finder::isLuminixModel(string|object $class)`

Verifica se uma classe é um modelo Luminix válido (usa a trait `LuminixModel` ou implementa `LuminixModelInterface`):

```php
Finder::isLuminixModel(App\Models\User::class);
// true

Finder::isLuminixModel(App\Models\SomeOtherModel::class);
// false
```

### `Finder::classUses(string $class, string $trait, bool $recursive = true)`

Verifica se uma classe usa determinada trait (opcionalmente de forma recursiva):

```php
use Illuminate\Database\Eloquent\SoftDeletes;

Finder::classUses(App\Models\ToDo::class, SoftDeletes::class);
// true ou false
```

## Exemplo de Uso

Você pode usar a façade para gerar listas dinâmicas de modelos em funcionalidades como menus de administração ou formulários de seleção:

```php
use Luminix\Backend\Facades\Finder;

$options = Finder::all()->mapWithKeys(function ($class, $alias) {
    return [$alias => $class::getDisplayName()['plural']];
});
// ['user' => 'Users', 'to_do' => 'To Dos', ...]
```
