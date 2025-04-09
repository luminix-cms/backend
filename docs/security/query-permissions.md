# Permissões em Nível de Consulta

## Visão Geral

As permissões em nível de consulta permitem filtrar registros dinamicamente com base nas permissões do usuário diretamente no banco de dados. Este recurso poderoso oferece controle de acesso refinado, eliminando registros não autorizados antes que sejam retornados ao cliente.

## Propósito

O método `scopeAllowed` fornece um mecanismo para:
- Restringir acesso a registros com base em condições específicas do usuário
- Implementar segurança em nível de linha (*row-level security*)
- Impedir que usuários não autorizados acessem ou modifiquem registros específicos

## Implementação

Para implementar permissões em nível de consulta, sobrescreva o método `scopeAllowed` em seu modelo com lógica personalizada que filtra registros com base nas permissões do usuário.

### Exemplo Básico

```php
use Illuminate\Database\Eloquent\Builder;

class Post extends Model
{
    /**
     * Filtra as consultas para incluir apenas registros que o usuário pode acessar.
     *
     * @param Builder $query Construtor de consultas do Eloquent
     * @param string $permission Tipo de permissão verificada (ex: 'read', 'update', 'delete')
     * @return void
     */
    public function scopeAllowed(Builder $query, string $permission)
    {
        // Apenas autores podem atualizar ou excluir seus próprios posts
        if (in_array($permission, ['update', 'delete'])) {
            $query->where('author_id', auth()->id());
        }
        
        // Lógica opcional para diferentes tipos de permissão
        if ($permission === 'read') {
            // Permite visualizar posts publicados ou rascunhos do próprio usuário
            $query->where(function ($q) {
                $q->where('status', 'published')
                   ->orWhere('author_id', auth()->id());
            });
        }
    }
}
```

## Conceitos-Chave

### Tipos de Permissão
O método `scopeAllowed` lida com todas as permissões mapeadas na configuração `security.permissions`. Os valores padrão possíveis são `read`, `create`, `update` e `delete`.

### Autenticação
Para utilizar `auth()->id()` e obter o ID do usuário atual, certifique-se de ter um middleware de autenticação configurado corretamente.

### Comportamento
- Se nenhum registro corresponder às condições permitidas, um conjunto vazio é retornado
- Segue o padrão `404 Not Found` para tentativas de acesso não autorizadas

## Casos de Uso Avançados

### Condições Múltiplas
Implemente lógicas complexas de permissão com múltiplas condições:

```php
public function scopeAllowed(Builder $query, string $permission)
{
    if ($permission === 'update') {
        $query->where(function ($q) {
            $q->where('author_id', auth()->id())
              ->orWhere('team_id', auth()->user()->team_id)
              ->orWhereHas('collaborators', function ($subQuery) {
                  $subQuery->where('user_id', auth()->id()); // Filtra colaboradores associados
              });
        });
    }
}
```

### Permissões Baseadas em Funções
Integre com sistemas de controle de acesso baseado em funções (*RBAC*):

```php
public function scopeAllowed(Builder $query, string $permission)
{
    if (auth()->user()->hasRole('admin')) {
        // Admins podem acessar todos os registros
        return;
    }
    
    // Aplica restrições para usuários não-administradores
    $query->where('organization_id', auth()->user()->organization_id);
}
```

## Boas Práticas

- Mantenha a lógica de permissão clara e concisa
- Utilize mecanismos existentes de autenticação e autorização
- Teste exaustivamente diferentes cenários de permissão
- Considere implicações de desempenho em escopos de consulta complexos

## Armadilhas Comuns

- Garanta aplicação consistente da lógica de permissão em diferentes métodos de consulta
- Cuidado com problemas de consultas N+1 ao usar permissões baseadas em relacionamentos
- Sempre valide permissões tanto no nível da consulta quanto na lógica da aplicação

