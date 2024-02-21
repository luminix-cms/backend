
# Luminix Backend

Esta é a documentação do Luminix Backend, um pacote para Laravel que permite a criação de APIs RESTful de forma rápida e fácil.

## Instalação

Para instalar o Luminix Backend, basta rodar o comando:

```bash
composer require luminix/backend
```

## Configuração

Após a instalação, publique os arquivos de configuração:

```bash
php artisan vendor:publish --tag=luminix-config
```

Após a publicação, você pode configurar o arquivo `config/luminix/backend.php` de acordo com suas necessidades.

### Lista de configurações

Configurações dentro de `luminix.backend`:

| Configuração | Descrição | Padrão |
| --- | --- | --- |
| `models.namespace` | Namespace onde serão buscados os modelos | `App\Models` |
| `models.include` | Modelos fora do namespace que devem ser incluídos | `[]` |
| `api.prefix` | Prefixo das rotas da API | `luminix-api` |
| `api.max_per_page` | Máximo de itens por página | `150` |
| `api.controller` | Controlador base da API | `Luminix\Backend\Http\Controllers\ApiController` |
| `api.controller_overrides` | Controladores específicos para cada modelo | `[]` |
| `security.gates_enabled` | Habilita ou desabilita o uso de Gates | `true` |
| `security.middleware` | Middleware aplicada a todas as rotas | `['api', 'auth']` |
| `security.permissions` | Mapeamento de ações a permissões que serão aplicados nos Gates e na consulta de permitidos | `['index' => 'read', 'show' => 'read', 'store' => 'create', 'update' => 'update', 'destroy' => 'delete', 'destroyMany' => 'delete', 'restoreMany' => 'update', 'import' => 'create', 'export' => 'read']` |

## Conteúdos

 - [Modelos Luminix](./2-Modelos-luminix.md)
   - [Campos preenchíveis](./2-Modelos-luminix.md#Campos-preenchíveis)
   - [Relacionamentos](./2-Modelos-luminix.md#Relacionamentos)
 - [Ajustando a API](./3-Ajustando-a-api.md)
   - [Modificando rotas](./3-Ajustando-a-api.md#Modificando-rotas)
   - [Validação de dados](./3-Ajustando-a-api.md#Validação-de-dados)
   - [Saída dos dados](./3-Ajustando-a-api.md#Saída-dos-dados)
   - [Abas](./3-Ajustando-a-api.md#Abas)
   - [Busca](./3-Ajustando-a-api.md#Busca)
   - [Filtros e ordenação](./3-Ajustando-a-api.md#Filtros-e-ordenacao)
   - [Eager Loading](./3-Ajustando-a-api.md#Eager-Loading)
   - [Importação e exportação](./3-Ajustando-a-api.md#Importação-e-exportação)
   - [Criando um controlador](./3-Ajustando-a-api.md#Criando-um-controlador)
   - [Recursos](./3-Ajustando-a-api.md#Recursos)
 - Segurança
   - Consulta de permissão
   - Gates