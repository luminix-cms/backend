# Personalizar Endpoints

O Luminix oferece várias maneiras de substituir o comportamento padrão da API REST. Aqui estão algumas das formas mais comuns de personalizar o comportamento dos seus controladores de API.

## Definir os campos preenchíveis (fillable)

Por padrão, o Luminix usa a propriedade `fillable` do modelo para determinar quais campos podem ser atribuídos em massa. Você pode substituir essa propriedade no modelo para especificar quais campos são permitidos ao usar os métodos `store` e `update`.

```php
class ToDo extends Model
{
    protected $fillable = ['title', 'description', 'due_date'];
}
```

Neste exemplo, apenas os campos `title`, `description` e `due_date` podem ser definidos usando os métodos `store` e `update`. Todos os outros campos serão ignorados, a menos que sejam tratados manualmente em um controlador personalizado ou em um evento do modelo.

## Adicionar comportamentos ao próprio modelo

A maneira mais fácil de personalizar o comportamento de um modelo é adicionar comportamentos diretamente à classe do modelo. Por exemplo, utilize os [Eventos do Modelo Eloquent](https://laravel.com/docs/11.x/eloquent#events) integrados do Laravel para adicionar lógica personalizada aos seus modelos.

```php
class ToDo extends Model
{
    protected static function booted()
    {
        static::creating(function ($todo) {
            $todo->user_id = auth()->id();
        });
    }
}
```

No exemplo acima, o evento `creating` é usado para definir automaticamente o campo `user_id` do modelo `ToDo` como o ID do usuário autenticado. Isso garante que o usuário que criou o `ToDo` esteja sempre associado a ele.

Outros recursos importantes a considerar são [Atributos Ocultos](https://laravel.com/docs/11.x/eloquent-serialization#hiding-attributes-from-json), [Accessors e Mutators](https://laravel.com/docs/11.x/eloquent-mutators) e [Conversão de Atributos (Attribute Casting)](https://laravel.com/docs/11.x/eloquent-mutators#attribute-casting).

Lembre-se de que essa abordagem será aplicada globalmente ao modelo, portanto pode não ser adequada para todos os casos de uso.

## Ouvir eventos do Luminix

O Luminix complementa o sistema de eventos do Eloquent ao registrar [eventos específicos da API](../digging-deeper/events.md), que são acionados durante o processamento de uma requisição. Isso pode ser útil para adicionar lógica personalizada aos endpoints da API, como registro de logs, auditoria ou envio de notificações.

## Adicionar controladores específicos do modelo

Se você precisar personalizar ainda mais o comportamento da API REST de um modelo específico, pode criar um novo controlador que estende o controlador padrão do Luminix. Por exemplo, se você tem um modelo `User`, pode criar uma classe `UserController` que estende a classe `Luminix\Backend\Controllers\ResourceController`:

```php
use Luminix\Backend\Controllers\ResourceController;

class UserController extends ResourceController
{
    // Sobrescrever o comportamento padrão aqui
}
```

Em seguida, você precisa atribuir o novo controlador ao modelo adicionando o atributo `WithController` à classe do modelo:

```php
use App\Http\Controllers\UserController;
use Luminix\Backend\Model\LuminixModel;
use Luminix\Backend\Controllers\WithController;

#[WithController(UserController::class)]
class User extends Model
{
    use LuminixModel;
}
```

Isso informará ao Luminix para usar a classe `UserController` em todos os endpoints da API relacionados ao modelo `User`.

### Ganchos (hooks) do controlador

O `ResourceController` possui alguns métodos que podem ser sobrescritos para personalizar o comportamento na maioria dos casos aplicáveis:

- `beforeTransaction`: chamado antes de iniciar uma transação. Você pode usá-lo para executar ações que devem ocorrer antes de qualquer operação de banco de dados.

- `afterTransaction`: chamado após a transação ser concluída. Você pode usá-lo para executar ações que devem ocorrer após todas as operações de banco de dados.

- `beforeSave`: chamado antes de salvar uma instância do modelo. Você pode usá-lo para modificar a instância antes de ser salva no banco de dados.

- `afterSave`: chamado após salvar uma instância do modelo. Você pode usá-lo para executar ações adicionais após o salvamento.

- `beforeCreate`: chamado antes de criar uma nova instância do modelo. Você pode usá-lo para modificar a instância antes de ser criada.

- `afterCreate`: chamado após criar uma nova instância do modelo. Você pode usá-lo para executar ações adicionais após a criação.

- `beforeUpdate`: chamado antes de atualizar uma instância existente do modelo. Você pode usá-lo para modificar a instância antes de ser atualizada.

- `afterUpdate`: chamado após atualizar uma instância existente do modelo. Você pode usá-lo para executar ações adicionais após a atualização.

- `beforeDelete`: chamado antes de excluir uma instância do modelo. Você pode usá-lo para modificar a instância antes de ser excluída.

- `afterDelete`: chamado após excluir uma instância do modelo. Você pode usá-lo para executar ações adicionais após a exclusão.

- `beforeRestore`: chamado antes de restaurar uma instância excluída via soft-delete. Disponível apenas para modelos que utilizam `SoftDeletes`.

- `afterRestore`: chamado após restaurar uma instância excluída via soft-delete.

- `onTransactionError`: chamado quando ocorre um erro dentro da transação de banco de dados. Recebe o erro, a requisição e o item. Por padrão, relança o erro. Pode ser sobrescrito para tratamento personalizado.

Todos os hooks recebem o objeto `Request` e a instância do modelo como parâmetros, exceto `onTransactionError` que recebe também o `Throwable` como primeiro argumento.

Aqui está um exemplo de como usar esses métodos em um controlador personalizado:

```php
use Illuminate\Http\Request;
use Luminix\Backend\Controllers\ResourceController;

class UserController extends ResourceController
{
    protected function beforeSave(Request $request, $item)
    {
        $avatar = $request->file('avatar');

        if ($avatar) {
            $filename = \Str::random(10) . '.' . $avatar->getClientOriginalExtension();
            \Storage::disk('public')->put('avatars/' . $filename, $avatar);
            $item->avatar = $filename;
        }
    }
}
```

Neste exemplo, o método `beforeSave` é usado para salvar uma imagem de avatar no disco `public` antes de salvar a instância do modelo `User`. Neste caso específico, é preferível que o campo `avatar` não seja preenchível (fillable) no modelo, pois está sendo definido manualmente. Além disso, você não deve chamar `save` na instância do modelo no método `beforeSave`, pois ela será salva posteriormente pelo controlador.

### Sobrescrever métodos de ação

Você também pode sobrescrever os métodos de ação padrão no controlador para personalizar o comportamento de ações específicas. Por exemplo, você pode sobrescrever o método `store` para implementar sua própria lógica de criação de um novo recurso. Lembre-se de que, usando essa abordagem, você precisará tratar toda a lógica da ação, incluindo validação e tratamento de erros.

```php
use Luminix\Backend\Controllers\ResourceController;

class UserController extends ResourceController
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = new User($data);
        $user->save();

        return response()->json($user, 201);
    }
}
```

### Adicionar ações personalizadas

Se você precisar adicionar ações personalizadas à API de um modelo, pode fazer isso sobrescrevendo o método `getLuminixRoutes` no modelo. Esse método deve retornar um array de rotas personalizadas que você deseja adicionar ao serviço de roteamento.

```php
use Luminix\Backend\Model\LuminixModel;
use Luminix\Backend\Services\RouteGenerator;

class User extends Model
{
    use LuminixModel;

    static function getLuminixRoutes(): array
    {
        // Gerar as rotas padrão
        $routes = RouteGenerator::make(static::class);

        // Mapear o método 'profile' do controlador para o caminho 'users/profile'
        $routes['profile'] = 'users/profile';

        // Para definir o método HTTP ou adicionar middleware, defina o valor como um array
        $routes['addAvatar'] = [
            'method' => 'post', // opcional
            'path' => 'users/{id}/add-avatar', // obrigatório
            'middleware' => ['can:update-profile'] // opcional
        ];

        // Desabilitar a rota padrão 'destroy'
        unset($routes['destroy']);

        // Personalizar o caminho da rota index
        $routes['index'] = 'custom-users-path';

        return $routes;
    }
}
```

Alternativamente, você pode adicionar um [reducer](https://github.com/AranduTech/php-reducible) ao serviço `RouteGenerator` para modificar as rotas de determinados modelos. Os reducers devem ser adicionados no método `register` de qualquer provedor de serviços carregado pela sua aplicação. O nome do reducer deve ser `'model{$ModelName}Routes'`, onde `{$ModelName}` é o nome da classe do modelo sem o namespace.

```php
use Luminix\Backend\Services\RouteGenerator;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        RouteGenerator::reducer('modelUserRoutes', function ($routes) {
            $routes['profile'] = 'users/profile';
            $routes['addAvatar'] = [
                'method' => 'post',
                'path' => 'users/{id}/add-avatar',
                'middleware' => ['can:update-profile']
            ];
            unset($routes['destroy']);
            $routes['index'] = 'custom-users-path';
            return $routes;
        });
    }
}
```

Em seguida, a lógica para cada ação personalizada no controlador deve ser implementada como um novo método:

```php
use Luminix\Backend\Controllers\ResourceController;

class UserController extends ResourceController
{
    public function addAvatar(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $avatar = $request->file('avatar');
        $filename = \Str::random(10) . '.' . $avatar->getClientOriginalExtension();
        \Storage::disk('public')->put('avatars/' . $filename, $avatar);
        $user->avatar = $filename;
        $user->save();

        return response()->json($user);
    }

    public function profile(Request $request)
    {
        // ...
    }
}
```

O método mostrado pode ser acessado enviando uma requisição `POST` para `/luminix-api/users/{id}/add-avatar` com o arquivo `avatar` no corpo da requisição.

> **Nota:** Qualquer método personalizado no controlador deve tratar toda a lógica por conta própria, incluindo validação e tratamento de erros.

### Adicionar métodos ao controlador base

Uma maneira menos drástica de personalizar o comportamento de todos os controladores é adicionar métodos à classe do controlador base. Isso é útil para adicionar funcionalidades compartilhadas entre todos os controladores, como tratamento de erros personalizado ou registro de logs.

Para isso, você precisa adicionar uma macro à classe `ResourceController`. Isso pode ser feito no método `boot` do seu `App\Providers\AppServiceProvider`:

```php
use Luminix\Backend\Controllers\ResourceController;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        ResourceController::macro('logRequest', function ($request) {
            \Log::info('Requisição recebida', ['url' => $request->url()]);
        });
    }
}
```

Isso habilitará a ação `logRequest` em todos os grupos de rotas dos modelos. Você ainda precisa atribuir a ação a um caminho no método `getLuminixRoutes` do modelo.

É possível adicionar um reducer a `'modelRoutes'` para atribuir a ação a todos os modelos de uma vez:

```php
use Luminix\Backend\Services\RouteGenerator;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        RouteGenerator::reducer('modelRoutes', function ($routes) {
            // Adiciona a ação 'logRequest' a todos os modelos usando o método GET
            $routes['logRequest'] = 'log-request';
            return $routes;
        });
    }
}
```
