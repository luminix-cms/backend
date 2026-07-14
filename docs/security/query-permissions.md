# Permissões em Nível de Consulta

## Visão Geral

As permissões em nível de consulta permitem filtrar registros dinamicamente com base nas permissões do usuário diretamente no banco de dados. Este recurso poderoso oferece controle de acesso refinado, eliminando registros não autorizados antes que sejam retornados ao cliente.

## Propósito

O método `scopeAllowed` fornece um mecanismo para:
- Restringir acesso a registros com base em condições específicas do usuário
- Implementar segurança em nível de linha (*row-level security*)
- Impedir que usuários não autorizados acessem ou modifiquem registros específicos

O `scopeAllowed` responde a uma única pergunta: **"quais linhas este usuário pode alcançar nesta operação?"**. Ele é um filtro de consulta — tudo o que não puder ser expresso como um `WHERE` sobre as linhas do modelo pertence a outro mecanismo (veja [O que o scopeAllowed não deve fazer](#o-que-o-scopeallowed-não-deve-fazer)).

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
     * @param string $permission Tipo de permissão verificada ('read', 'update' ou 'delete')
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

## O Contrato: quando e com qual permissão

O `scopeAllowed` é chamado **automaticamente** nas consultas geradas pelo Luminix, desde que a ação possua uma permissão mapeada em `security.permissions`. Você não precisa chamá-lo manualmente; basta definir a implementação no modelo.

A tabela abaixo fixa qual string de permissão o `scopeAllowed` recebe em cada endpoint (com o mapeamento padrão de `security.permissions`):

| Endpoint | Permissão recebida | Consulta em que é aplicado |
|---|---|---|
| `index` | `'read'` | listagem |
| `show` | `'read'` | busca do item |
| `store` | — *(nunca é chamado)* | — |
| `update` | `'update'` | busca do item **antes** da escrita |
| `destroy` / `destroyMany` | `'delete'` | busca do(s) item(ns) antes da exclusão |
| `restoreMany` | `'update'` | busca dos itens antes da restauração |
| `sync` / `attach` / `detach` | `'update'` | busca do **registro pai** antes da escrita |

Dois princípios resumem a tabela:

1. **Toda busca que precede uma escrita é escopada.** Um registro oculto pelo `scopeAllowed` resulta em `404` antes de qualquer modificação no banco — para o usuário, o registro não existe.
2. **A resposta de uma escrita confirmada nunca é escopada.** Após um `store`, `update`, `sync`, `attach` ou `detach` bem-sucedido, o item é rebuscado **sem** o `scopeAllowed` para compor a resposta. Uma escrita persistida sempre responde com o item (`201` no `store`, `200` nos demais), mesmo que o escopo oculte o registro do próprio autor — por exemplo, ao criar ou reatribuir um registro para outro usuário.

### Por que o `store` nunca chama o `scopeAllowed`

Um filtro de linhas não tem como expressar "pode criar": no momento do `store`, a linha ainda não existe para ser filtrada. A autorização de criação é responsabilidade do Gate `create-{apelido_modelo}` (veja [Utilizando Gates](./using-gates.md)) e das regras de validação do modelo.

### Endpoints de relação autorizam como atualização do pai

`sync`, `attach` e `detach` são tratados como uma **atualização do modelo pai**: a busca do registro pai aplica `scopeAllowed('update')` e o Gate `update-{apelido_modelo}` é verificado com o registro pai. Se o usuário não pode atualizar o registro, ele não pode alterar suas relações.

## Conceitos-Chave

### Tipos de Permissão

Com o mapeamento padrão, o `scopeAllowed` recebe apenas `'read'`, `'update'` ou `'delete'`. A string `'create'` nunca chega ao `scopeAllowed`. Se você personalizar `security.permissions`, as strings configuradas são repassadas como estão.

### Autenticação

Para utilizar `auth()->id()` e obter o ID do usuário atual, certifique-se de ter um middleware de autenticação configurado corretamente.

### Códigos de resposta

- Registro oculto pelo `scopeAllowed`: `404 Not Found` (em listagens, o registro simplesmente não aparece)
- Acesso negado por um Gate: `401` com a mensagem `unauthorized`

Essa distinção é intencional: o escopo faz o registro *não existir* para o usuário; o Gate nega uma ação sobre um registro que o usuário consegue alcançar.

## O que o scopeAllowed não deve fazer

Use o `scopeAllowed` somente para responder "quais linhas este usuário alcança". Ele **não** deve ser usado para:

- **Autorizar criação.** O `scopeAllowed` nunca é consultado no `store`. Use o Gate `create-{apelido_modelo}`.
- **Validar o conteúdo da requisição.** Restringir quais valores um campo pode receber (por exemplo, impedir que `user_id` aponte para outro usuário) é papel das [regras de validação](../basics/validation.md) ou dos Gates — não de um filtro de linhas.
- **Controlar a resposta de uma escrita.** A rebusca pós-escrita ignora o escopo por contrato; não conte com o `scopeAllowed` para ocultar ou transformar o item retornado após uma escrita bem-sucedida.
- **Filtrar atributos ou relacionamentos.** O escopo filtra *linhas do próprio modelo*. Para ocultar atributos use `$hidden`/Resources; para restringir eager loading, veja [Eager Loading](../digging-deeper/eager-loading.md).

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
- Diferencie as permissões recebidas (`'read'`, `'update'`, `'delete'`) em vez de aplicar um filtro único e incondicional — regras de leitura e de escrita raramente são idênticas
- Utilize mecanismos existentes de autenticação e autorização
- Teste exaustivamente diferentes cenários de permissão
- Considere implicações de desempenho em escopos de consulta complexos

## Armadilhas Comuns

- Garanta aplicação consistente da lógica de permissão em diferentes métodos de consulta
- Cuidado com problemas de consultas N+1 ao usar permissões baseadas em relacionamentos
- Sempre valide permissões tanto no nível da consulta quanto na lógica da aplicação
