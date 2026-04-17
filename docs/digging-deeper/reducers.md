# Elementos Reducíveis

O Luminix utiliza o pacote [Reducible](https://github.com/AranduTech/php-reducible) para permitir customizações do comportamento do pacote. Este documento descreve os reducers disponíveis e como utilizá-los.

## Reducers Disponíveis

### `ModelFinder::models()`

Destinado a desenvolvedores de pacotes que desejam adicionar modelos à geração de endpoints de API. Por padrão, o Luminix busca modelos no diretório `app/Models`. Para customizar esse comportamento, registre um reducer:

```php
use Luminix\Backend\Services\ModelFinder;

public function boot()
{
    ModelFinder::reducer('models', function ($models) {
        return array_merge($models, [
            \Vendor\Package\Models\CustomModel::class,
        ]);
    });
}
```

### `RouteGenerator::modelRoutes()`

Modifica o array de rotas gerado pelo Luminix para **todos** os modelos. Útil para adicionar rotas comuns a todos os endpoints:

```php
use Luminix\Backend\Services\RouteGenerator;

public function boot()
{
    RouteGenerator::reducer('modelRoutes', function ($routes, $slug) {
        return array_merge($routes, [
            'custom' => [
                'method' => 'post',
                // users/custom, to-dos/custom, etc.
                'path' => "{$slug}/custom",
            ],
        ]);
    });
}
```

### `RouteGenerator::model{$Model}Routes()`

Similar ao anterior, mas aplica customizações a um modelo **específico**. O nome do reducer deve ser `model{NomeDaClasse}Routes`, onde `{NomeDaClasse}` é o nome da classe sem namespace:

```php
use Luminix\Backend\Services\RouteGenerator;

public function boot()
{
    RouteGenerator::reducer('modelUserRoutes', function ($routes) {
        return array_merge($routes, [
            'custom' => [
                'method' => 'post',
                'path' => 'users/custom',
            ],
        ]);
    });
}
```

> **Dica:** Os reducers de rotas devem ser registrados no método `register` do service provider, não no `boot`, para garantir que estejam disponíveis no momento do carregamento das rotas. Veja também [Personalizar Endpoints](../basics/customize-endpoints.md) para alternativas em nível de modelo.
