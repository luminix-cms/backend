# Busca

Os endpoints da API que retornam respostas paginadas aceitam o parâmetro `q` para realizar buscas em registros:

```
GET /luminix-api/users?q=João
```

## Funcionalidade Padrão de Busca

Por padrão, o Luminix implementa uma busca básica usando operadores `LIKE` no banco de dados. O sistema:

- Busca o termo em **todas as colunas preenchíveis (fillable)** do modelo
- Usa a sintaxe `%termo%` para correspondência parcial
- Combina os resultados com operadores `OR`

### Exemplo de Comportamento Padrão
Para uma busca com `?q=João`, será gerada uma query semelhante a:
```sql
WHERE name LIKE '%João%' OR email LIKE '%João%' OR ... (todas colunas fillable)
```

## Personalizando a Busca

Para controle avançado, sobrescreva o método `scopeSearch` no seu modelo. Isso permite:

- Definir colunas específicas para busca
- Implementar lógica complexa de pesquisa
- Adicionar relacionamentos à busca
- Utilizar operadores diferentes

### Exemplo de Implementação Customizada
```php
use Illuminate\Database\Eloquent\Builder;

class User extends Model
{
    public function scopeSearch(Builder $query, string $search)
    {
        // Busca em colunas específicas com operadores controlados
        $query->where(function ($subQuery) use ($search) {
            $subQuery->where('name', 'like', "%{$search}%")
                     ->orWhere('email', 'like', "{$search}%"); // Começa com o termo
        });
        
        // Adiciona busca em relacionamento (ex: perfil)
        $query->orWhereHas('profile', function ($profileQuery) use ($search) {
            $profileQuery->where('bio', 'like', "%{$search}%");
        });
    }
}
```

### Boas Práticas
1. **Performance**: Evite buscas em colunas não indexadas
2. **Segurança**: Mantenha a lista de colunas pesquisáveis explícita
3. **Relevância**: Priorize colunas mais significativas para o usuário final
4. **Consistência**: Mantenha padrões de operadores entre modelos

## Busca Avançada com Filtros Combinados

Combine o parâmetro `q` com o sistema de [filtragem](filtering.md) para consultas precisas:

```http
GET /luminix-api/users?q=João&where[created_at:greaterThan]=2024-01-01&order_by=name:asc
```

Esta requisição retornará:
1. Registros contendo "João" em colunas pesquisáveis
2. Criados após 1º de Janeiro de 2024
3. Ordenados alfabeticamente por nome

## Futuras Implementações

### Integração com Laravel Scout (Roadmap)
Uma integração nativa com o [Laravel Scout](https://laravel.com/docs/scout) está planejada para versões futuras. Isso trará:

- Suporte a motores de busca full-text (Algolia, Meilisearch, etc.)
- Indexação automática de modelos
- Sintaxe de busca avançada
- Resultados com relevância ponderada

### Exemplo Antecipado (Conceptual)
```php
class Product extends Model
{
    use LuminixModel, Searchable;

    public function toSearchableArray()
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category->name,
        ];
    }
}
```
```http
GET /luminix-api/products?q=celular+5g+promoção
```
Resultados priorizariam correspondências exatas e relevância semântica.

## Limitações Atuais
- Busca simultânea em múltiplos modelos não suportada
- Limitação de performance em grandes datasets

> **Dica:** Para necessidades complexas de busca, considere criar endpoints customizados enquanto aguarda a integração com Scout.