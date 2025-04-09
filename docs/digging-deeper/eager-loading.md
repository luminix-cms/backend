# Carregamento Ansioso (Eager Loading) de Relacionamentos no Luminix

O carregamento ansioso é uma técnica crucial no Laravel para otimizar consultas ao banco de dados, carregando modelos relacionados de forma eficiente. No Luminix, os desenvolvedores têm controle flexível sobre como os relacionamentos são carregados, evitando a necessidade de múltiplas requisições à API.

---

## Entendendo o Carregamento Ansioso

O carregamento ansioso permite que você carregue modelos relacionados junto com o modelo principal, reduzindo o número de consultas ao banco de dados e prevenindo o problema de consultas N+1. O Luminix oferece múltiplas abordagens para adicionar relacionamentos carregados ansiosamente às respostas da sua API.

---

## 1. Carregamento Global de Relacionamentos com a Propriedade `$with`

A propriedade `$with` oferece uma maneira simples de **sempre** carregar ansiosamente relacionamentos específicos para um modelo. É ideal para relacionamentos que são necessários consistentemente.

```php
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    // Carrega sempre os papéis (roles) quando um User é consultado
    protected $with = ['roles'];

    public function roles()
    {
        return $this->hasMany(Role::class);
    }
}
```

### Considerações Importantes
- ⚠️ Use com cautela: Esta abordagem **sempre** carrega os relacionamentos especificados, a menos que seja indicado o contrário na consulta.
- Risco de criar carregamento recursivo se os relacionamentos forem interdependentes.
- Pode impactar o desempenho se os relacionamentos envolverem muitos dados ou lógica complexa.

---

## 2. Carregamento Dinâmico de Relacionamentos com Escopos de Consulta

Para um controle mais granular, sobrescreva os métodos `scopeBeforeLuminix` ou `scopeAfterLuminix` para carregar relacionamentos dinamicamente com base em parâmetros da requisição.

```php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Luminix\Backend\Model\LuminixModel;

class User extends Model
{
    use LuminixModel;

    public function scopeBeforeLuminix(Builder $query, Request $request)
    {
        // Lista de relacionamentos permitidos
        $allowedRelationships = ['roles', 'permissions'];

        // Verifica se o parâmetro 'with' está presente na requisição
        if ($request->has('with')) {
            $requested = $request->input('with', []);
            $with = collect(is_string($requested) ? [$requested] : $requested);

            // Valida se os relacionamentos solicitados são permitidos
            if (!$with->every(fn ($relation) => in_array($relation, $allowedRelationships))) {
                abort(422, 'Parâmetro "with" inválido');
            }

            // Carrega o relacionamento 'roles' se solicitado
            if ($with->contains('roles')) {
                $query->with([
                    'roles' => fn ($query) => $query->allowed('read')
                    // Usa o escopo 'allowed' para carregar apenas roles
                    // que o usuário tem permissão de leitura (considerando
                    // que o modelo Role também usa LuminixModel e possui
                    // uma implementação de scopeAllowed)
                ]);
            }

            // ... Adicione outros relacionamentos conforme necessário
        }
    }
}
```

### Exemplos de Requisições na API

Carregar relacionamentos específicos dinamicamente:
```http
GET /luminix-api/users?with=roles
GET /luminix-api/users/1?with[]=roles&with[]=permissions
```

### Vantagens desta Abordagem
- **Controle refinado**: Permite definir exatamente quais relacionamentos podem ser carregados.
- **Flexibilidade**: Os consumidores da API podem solicitar apenas os dados necessários.
- **Segurança**: Relacionamentos sensíveis não são expostos inadvertidamente.

---

## Melhores Práticas

1. **Liste Relacionamentos Permitidos**: Defina explicitamente quais relacionamentos podem ser carregados ansiosamente para evitar acesso não autorizado.
2. **Monitore o Desempenho**: Relacionamentos complexos ou grandes volumes de dados podem impactar o tempo de resposta. Considere paginação ou limites.
3. **Use Carregamento Condicional**: Carregue relacionamentos apenas quando necessário (ex: parâmetros de requisição específicos).
4. **Valide Entradas do Usuário**: Sempre valide os parâmetros `with` para evitar injeção de consultas não intencionais.
5. **Evite Exposição de Dados Sensíveis**: Certifique-se de que relacionamentos carregados não exponham informações confidenciais inadvertidamente.

---

## Exemplo Avançado: Combinando Escopos e Filtros

Você pode combinar carregamento ansioso com filtros para otimizar ainda mais as consultas:

```php
public function scopeBeforeLuminix(Builder $query, Request $request)
{
    if ($request->has('with')) {
        $query->with([
            'posts' => function ($query) use ($request) {
                $query->where('status', 'published')
                      ->when($request->has('category'), function ($q) use ($request) {
                          $q->where('category_id', $request->category);
                      });
            }
        ]);
    }
}
```

**Requisição:**
```http
GET /luminix-api/users?with=posts&category=5
```

Neste exemplo, apenas posts publicados da categoria 5 serão carregados com os usuários.