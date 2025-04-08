# Referência de Configuração do Luminix Backend

Para publicar o arquivo de configuração, utilize o comando abaixo:

```bash
# Publica o arquivo de configuração do Luminix
php artisan vendor:publish --tag=luminix-config
```

## Configuração de Descoberta de Modelos

| Chave | Valor Padrão | Descrição |
|-----|--------------|-------------|
| `models.namespace` | `'App\Models'` | Namespace padrão onde o Luminix irá descobrir e escanear modelos. Todos os modelos neste namespace que utilizam a trait `LuminixModel` serão processados. |
| `models.include` | `[]` | Array de classes de modelos adicionais para inclusão manual no processamento. Útil para adicionar modelos de outros namespaces ou pacotes de terceiros. |

## Configuração da API

| Chave | Valor Padrão | Descrição |
|-----|--------------|-------------|
| `api.prefix` | `'luminix-api'` | Prefixo da URL para todas as rotas da API do Luminix. Permite personalizar o endpoint base da API. |
| `api.max_per_page` | `150` | Número máximo de itens retornados em uma única requisição da API. Previne uso excessivo de dados e melhora desempenho. |

## Configuração de Filtragem da API

| Chave | Valor Padrão | Descrição |
|-----|--------------|-------------|
| `api.filter.enable` | `true` | Habilita ou desabilita a funcionalidade de filtragem na API. Quando ativo, clientes podem filtrar resultados usando o parâmetro 'where'. |
| `api.filter.exclude` | `[]` | Lista de colunas excluídas da filtragem para modelos específicos. Previne filtragem em colunas sensíveis ou internas. Formato: `'NomeDaClasseModelo:coluna1,coluna2'`. Por padrão, as colunas `hidden` do modelo são excluídas. |
| `api.filter.throw` | `true` | Define se o Luminix deve lançar exceções em falhas de filtragem (ex.: coluna ou operador inválido). Auxilia na identificação e depuração de problemas. |

## Configuração de Segurança

| Chave | Valor Padrão | Descrição |
|-----|--------------|-------------|
| `security.gates_enabled` | `true` | Habilita verificações do Laravel Gate para permissões em nível de rota. Quando ativo, o Luminix aplica as permissões definidas na seção `permissions`. |
| `security.middleware` | `['api', 'auth']` | Middleware aplicado a todas as rotas da API do Luminix. Oferece uma camada adicional de segurança e autenticação. |
| `security.permissions` | Veja abaixo | Mapeia ações do controlador para tipos específicos de permissões. Permite controle de acesso granular. |

### Mapeamento Detalhado de Permissões

| Ação | Permissão | Descrição |
|--------|------------|-------------|
| `index` | `'read'` | Permissão necessária para listar/recuperar múltiplos recursos |
| `show` | `'read'` | Permissão necessária para recuperar um único recurso |
| `store` | `'create'` | Permissão necessária para criar um novo recurso |
| `update` | `'update'` | Permissão necessária para modificar um recurso existente |
| `destroy` | `'delete'` | Permissão necessária para excluir um único recurso |
| `destroyMany` | `'delete'` | Permissão necessária para excluir múltiplos recursos |
| `restoreMany` | `'update'` | Permissão necessária para restaurar múltiplos recursos |
| `sync` | `'update'` | Permissão necessária para sincronizar recursos |
| `attach` | `'update'` | Permissão necessária para vincular recursos relacionados |
| `detach` | `'update'` | Permissão necessária para desvincular recursos relacionados |

Essas permissões são concatenadas com o nome do modelo para formar a string de permissão final. Por exemplo, a permissão para a ação `index` no modelo `User` seria `'read-user'`.