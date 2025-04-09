# Utilizando Gates

O Luminix oferece segurança robusta através do sistema de Gates do Laravel, garantindo que o acesso à API seja rigidamente controlado e seguro por padrão.

## Filosofia de Segurança

Por padrão, o Luminix implementa uma abordagem "negar primeiro" para acesso à API. Isso significa:
- Nenhum endpoint da API é acessível sem permissão explícita
- Você tem controle total sobre quem pode acessar seus dados
- A segurança é aplicada no nível de ação individual

## Configurando Verificações de Gates

### Habilitando ou Desabilitando Verificações

Você pode habilitar ou desabilitar globalmente as verificações de Gates na configuração:

```php
// config/luminix/backend.php
'security' => [
    'gates_enabled' => true, // Padrão é true
    // Altere para false para desativar todas as verificações
],
```

### Definindo Requisitos de Permissão

Especifique as permissões necessárias para cada ação padrão da API:

```php
// config/luminix/backend.php
'security' => [
    'permissions' => [
        'index'   => 'read',    // Listar itens
        'show'    => 'read',    // Visualizar um item
        'store'   => 'create',  // Criar novos itens
        'update'  => 'update',  // Modificar itens existentes
        'destroy' => 'delete',  // Remover itens
        // ...
    ]
]
```

## Entendendo a Nomenclatura de Permissões

As permissões são geradas dinamicamente usando um padrão consistente:
- Formato: `{permissão}-{apelido_modelo}`
- Exemplos:
  - Ler usuários: `read-user`
  - Criar tarefas: `create-to_do`
  - Atualizar posts: `update-post`

> **Nota:** O `apelido_modelo` é derivado do nome da classe do modelo em formato snake_case.

## Implementando Definições de Gates

Defina suas permissões no método `boot()` de qualquer service provider. Recomenda-se [criar um service provider dedicado](https://laravel.com/docs/11.x/providers#writing-service-providers) para este propósito:

```php
// app/Providers/AuthServiceProvider.php
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\ToDo;

public function boot()
{
    // Permitir que usuários vejam apenas seu próprio perfil
    Gate::define('read-user', function (?User $currentUser, User $targetUser) {
        return !!$currentUser && $currentUser->id === $targetUser->id;
    });

    // Permitir que qualquer usuário autenticado crie tarefas
    Gate::define('create-to_do', function (?User $user) {
        return !!$user;
    });

    // Restringir atualizações de tarefas ao dono
    Gate::define('update-to_do', function (?User $user, ToDo $todo) {
        return !!$user && $user->id === $todo->user_id;
    });
}
```

## Considerações Importantes

### Comportamento em Endpoints de Listagem
- Ao recuperar uma lista de itens, a permissão é verificada **para cada item individualmente**
- O usuário deve ter permissão para todos os itens da lista
- Se qualquer item negar a permissão, a requisição é abortada com uma resposta Forbidden (403)

### Depuração de Permissões
- Use métodos do facade `Gate` como `allows()` e `denies()` para testar permissões:
```php
// Exemplo: Verificar se um usuário pode atualizar uma tarefa
if (Gate::allows('update-to_do', $todo)) {
    // Ação permitida
}
```

## Boas Práticas
- Comece com permissões restritivas
- Utilize o princípio do menor privilégio
- Faça auditorias regulares nas definições de Gates
- Considere controle de acesso baseado em roles para cenários complexos
- Documente claramente as regras de permissão para cada modelo

## Exemplo Avançado: Controle Hierárquico

Para cenários com hierarquias complexas (ex: departamentos em uma organização):

```php
Gate::define('update-department', function (User $user, Department $department) {
    // Permite se o usuário é administrador do departamento 
    // ou possui permissão herdada de um departamento pai
    return $user->isDepartmentAdmin($department) 
        || $department->ancestors()->whereHas('admins', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->exists();
});
```

## Integração com Policies

Para modelos complexos, considere usar [Policies do Laravel](https://laravel.com/docs/11.x/authorization#creating-policies) para organizar melhor as regras:

```php
// Definindo uma Policy para o modelo Post
class PostPolicy
{
    public function update(User $user, Post $post)
    {
        return $user->id === $post->author_id 
            || $user->hasRole('editor');
    }
}

// Registrando a Policy
Gate::policy(Post::class, PostPolicy::class);
```