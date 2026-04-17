# Referência da API

## Visão Geral

O Luminix Backend simplifica o desenvolvimento de APIs ao gerar automaticamente endpoints RESTful para seus modelos Laravel. Este recurso poderoso permite que desenvolvedores:

- Criem rapidamente interfaces de API padronizadas
- Reduzam código repetitivo
- Implementem segurança e filtragem consistentes
- Personalizem e estendam facilmente comportamentos padrão

## Convenções de Nomeação de Endpoints

### Estrutura de URLs

Todos os endpoints seguem um padrão consistente:
- Prefixo: `/luminix-api` (configurável)
- Representação do Modelo: Apelido do modelo no plural e *slugificado*

#### Exemplos de Transformação em Slug

| Nome do Modelo | Apelido do Modelo | Slug do Modelo |
|------------|-------------|------------|
| `App\Models\User` | `user` | `users` |
| `App\Models\ToDo` | `to_do` | `to-dos` |

> **Nota**: A *slugificação* converte underscores em hífens e garante nomes amigáveis para URLs.

## Operações CRUD

### Listagem de Registros: `GET /{$prefix}/{$modelSlug}`

Recupera uma lista paginada de registros com opções avançadas de filtragem.

#### Parâmetros de Consulta

| Parâmetro | Tipo | Descrição | Exemplo |
|-----------|------|-------------|---------|
| `page` | Inteiro | Número da página na paginação | `?page=2` |
| `per_page` | Inteiro | Registros por página | `?per_page=10` |
| `q` | String | Busca pelo termo especificado | `?q=João` |
| `order_by` | String | Ordena resultados (`coluna:direção`) | `?order_by=email:desc` |
| `where` | Array | Filtragem avançada | `?where[idade:greaterThan]=18` |
| `tab` | String | Filtragem personalizada por aba | `?tab=ativos` |
| `minified` | Booleano | Retorna apenas `id` + campo de label (útil para selects/dropdowns) | `?minified=1` |

#### Exemplos de Requisições

```javascript
// Exemplo de filtro abrangente
axios.get('/luminix-api/users', {
    params: {
        page: 2,
        per_page: 10,
        order_by: 'email:desc',
        where: {
            'name:contains': 'João',
            'age:between': [18, 30],
            'email_verified_at:notNull': 1
        }
    }
});
```

### Criação de Registros: `POST /{$prefix}/{$modelSlug}`

Cria novos registros com proteção interna de atributos.

```javascript
axios.post('/luminix-api/to-dos', {
    title: 'Comprar mantimentos',
    description: 'Leite, ovos, pão e manteiga',
    due_date: '2024-12-31'
});
```

> **Dica**: Apenas atributos preenchíveis (fillable) serão definidos por padrão. Considere [adicionar regras de validação](validation.md) para integridade dos dados, ou [sobrescrever o controlador](customize-endpoints.md#add-model-specific-controllers) para maior controle no processo de criação.

### Busca de Registro Único: `GET /{$prefix}/{$modelSlug}/{{primary_key}}`

Busca um registro específico pela sua chave primária.

```
GET /luminix-api/users/1
GET /luminix-api/to-dos/14
```

### Atualização de Registros: `POST /{$prefix}/{$modelSlug}/{{primary_key}}`

Atualiza registros existentes com opção de restauração.

```javascript
axios.post('/luminix-api/to-dos/14', {
    title: 'Lista de Compras Atualizada'
});

// Restaurar registro excluído via soft-delete
axios.post('/luminix-api/to-dos/14?restore=1');
```

### Exclusão de Registros

#### Registro Único: `DELETE /{$prefix}/{$modelSlug}/{{primary_key}}`

```
DELETE /luminix-api/users/1
DELETE /luminix-api/to-dos/14?force=1  // Exclusão permanente
```

#### Múltiplos Registros: `DELETE /{$prefix}/{$modelSlug}`

```javascript
axios.delete('/luminix-api/to-dos', {
    data: { ids: [14, 15, 16] }
});
```

### Restauração de Múltiplos Registros: `POST /{$prefix}/{$modelSlug}/restore`

Disponível apenas para modelos que utilizam `SoftDeletes`. Restaura registros previamente excluídos via soft-delete.

```javascript
axios.post('/luminix-api/to-dos/restore', {
    ids: [14, 15, 16]
});
```

> **Nota:** Para restaurar um único registro, utilize o endpoint de atualização com o parâmetro `?restore=1`:
> ```
> POST /luminix-api/to-dos/14?restore=1
> ```

## Gerenciamento de Relacionamentos

O Luminix suporta operações avançadas para relacionamentos muitos-para-muitos.

### Pré-requisitos

Defina relacionamentos sincronizáveis no seu modelo:

```php
class User extends Model
{
    use LuminixModel;

    protected $syncs = ['roles'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}
```

### Operações de Relacionamento

#### Sincronização: `POST /{$prefix}/{$modelSlug}/{{primary_key}}/{{relation}}/sync`

```javascript
// Sincronização simples
axios.post('/luminix-api/users/1/roles/sync', [1, 2, 3]);

// Sincronização com dados do pivot
axios.post('/luminix-api/users/1/roles/sync', [
    { id: 1, expires_at: '2024-12-31' }
]);
```

#### Vincular: `POST /{$prefix}/{$modelSlug}/{{primary_key}}/{{relation}}/{{related_primary_key}}`

```javascript
// Vinculação simples
axios.post('/luminix-api/users/1/roles/4');

// Vinculação com dados do pivot
axios.post('/luminix-api/users/1/roles/4', {
    expires_at: '2024-12-31'
});
```

#### Desvincular: `DELETE /{$prefix}/{$modelSlug}/{{primary_key}}/{{relation}}/{{related_primary_key}}`

```
DELETE /luminix-api/users/1/roles/4
```