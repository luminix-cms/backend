# Filtragem Baseada em Abas

O Luminix permite a criação de "abas" para filtrar dados com base em condições pré-definidas. Este recurso é útil quando você deseja oferecer aos usuários filtros pré-configurados para casos de uso comuns. Por padrão, modelos com `SoftDeletes` possuem uma aba "trashed" para recuperar registros excluídos via soft-delete.

```http
GET /luminix-api/users?tab=trashed
```

## Registrando Abas Personalizadas

Para definir abas personalizadas, você precisa sobrescrever o método `scopeWhereBelongsToTab` em seu modelo. Este método deve conter a lógica para filtrar registros com base no nome da aba, incluindo a aba "trashed" caso deseje mantê-la.

```php
use Illuminate\Database\Eloquent\Builder;

class User extends Model
{
    /**
     * Aplica consultas para incluir apenas registros que pertencem à aba especificada.
     *
     * @param Builder $query O construtor de consultas Eloquent atual
     * @param string $tab O nome da aba sendo filtrada
     * @return void|Builder
     */
    public function scopeWhereBelongsToTab(Builder $query, string $tab)
    {
        return match ($tab) {
            'trashed' => $query->onlyTrashed(), // Filtra registros excluídos
            'active' => $query->where('status', 'active'), // Filtra usuários ativos
            default => $query, // Sem filtro adicional para outras abas
        };
    }
}
```

### Funcionalidades Adicionais
1. **Combinação com Outros Filtros**: As abas podem ser usadas junto com parâmetros `where` para refinamento adicional:
```http
GET /luminix-api/users?tab=active&where[last_login:greaterThan]=2024-01-01
```

2. **Abas Dinâmicas**: Crie abas com lógica complexa usando relacionamentos ou cálculos:
```php
'high_value' => $query->whereHas('orders', fn($q) => $q->where('total', '>', 1000))
```

3. **Controle de Acesso**: Use [Gates do Laravel](api-reference.md#integracao-com-gates) para restringir abas específicas a determinados usuários.

> **Nota:** A aba "trashed" só estará disponível se o modelo utilizar `Illuminate\Database\Eloquent\SoftDeletes`.
