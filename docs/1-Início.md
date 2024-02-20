
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
| `security.permissions` | Mapeamento de ações a permissões | `['index' => 'read', 'show' => 'read', 'store' => 'create', 'update' => 'update', 'destroy' => 'delete', 'destroyMany' => 'delete', 'restoreMany' => 'update', 'import' => 'create', 'export' => 'read']` |

## Conteúdos

 - [Modelos Luminix](./2-Modelos-luminix.md)
   - [Campos preenchíveis](./2-Modelos-luminix.md#Campos-preenchíveis)
   - [Relacionamentos](./2-Modelos-luminix.md#Relacionamentos)
 - Ajustando a API
   - Modificando rotas
   - Validação de dados
   - Saída dos dados
   - Abas
   - Busca
   - Filtros e ordenação
   - Eager Loading
   - Importação e exportação
   - Criando um controlador
 - Segurança
   - Consulta de permissão
   - Gates