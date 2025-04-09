# Configuração de Middleware

A configuração de middleware é uma parte crucial da segurança do Luminix Backend. O middleware fornece uma camada adicional de segurança e autenticação para suas rotas de API, garantindo que apenas requisições autorizadas sejam processadas.

## Configuração de Segurança

### Middleware Global

| Chave | Valor Padrão | Descrição |
|-----|--------------|-------------|
| `security.middleware` | `['api', 'auth']` | Middleware aplicado a todas as rotas da API do Luminix. Isso garante que todas as requisições passem pela pilha de middleware especificada, fornecendo verificações essenciais de segurança e autenticação. |

Por padrão, o Luminix aplica os middlewares `api` e `auth` a todas as rotas da API. O grupo `api` geralmente inclui middlewares específicos para APIs, como limitação de taxa (*rate limiting*), enquanto o `auth` garante que apenas usuários autenticados possam acessar a API.

Você pode personalizar a pilha de middleware modificando a configuração `security.middleware` no arquivo `config/luminix/backend.php`. Por exemplo, você pode adicionar middlewares para logs, limitação de requisições (*throttling*) ou mecanismos de autenticação personalizados:

```php
return [
    'security' => [
        'middleware' => ['api', 'auth', App\Http\Middlewares\MinhaMiddleware::class], // Exemplo: middleware personalizado adicionado
    ],
];
```

### Middleware Específico por Endpoint

É possível definir middlewares para rotas específicas. Isso é alcançado [personalizando as ações do modelo](../basics/customize-endpoints.md#adicionar-ações-personalizadas).

O exemplo abaixo demonstra como alterar o middleware para a ação `index` do modelo `User`:

```php
use Luminix\Backend\Services\RouteGenerator;

public function register()
{
    RouteGenerator::reducer('modelUserRoutes', function ($rotas) {
        // Define um middleware personalizado apenas para a ação 'index'
        $rotas['index']['middleware'] = ['meu-middleware'];
        
        return $rotas;
    });
}
```

> A aplicação de middleware no nível da rota é feita dentro do grupo geral de middleware definido na configuração. Portanto, se você adicionar um middleware específico para uma rota, ele será aplicado juntamente com os middlewares globais.

#### Casos de Uso Comuns:
- **Autenticação diferenciada**: Use middlewares distintos para endpoints públicos e privados.
- **Controle de acesso granular**: Restrinja ações específicas (ex: `destroy`) a usuários com permissões elevadas.
- **Logs customizados**: Adicione um middleware para rastrear atividades em endpoints críticos.
