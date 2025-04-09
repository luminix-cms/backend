# Filtragem de Dados

A API Luminix oferece um sistema de filtragem poderoso que permite aos clientes recuperar apenas os dados necessários. Isso ajuda a criar uma API mais genérica e flexível, reduzindo o volume de dados transferidos e melhorando o desempenho.

## Ativando/Desativando a Filtragem

Por padrão, a filtragem está ativada para todos os modelos. Você pode desativar esse recurso definindo a chave de configuração `api.filter.enable` como `false`.

```php
// Arquivo de configuração do backend do Luminix
'api' => [
    'filter' => [
        'enable' => false,
    ],
],
```

## Sintaxe de Filtro

O sistema de filtragem utiliza o parâmetro de query `where` para aplicar filtros aos dados. O valor deste parâmetro deve ser um array de pares chave-valor, onde a chave pode ser o nome da coluna ou o nome da coluna seguido por dois pontos e um operador. O valor deve ser o critério desejado para a filtragem.

Aqui estão alguns exemplos de uso do parâmetro `where`:

```http
// Busca usuários com o nome exato "João Silva"
GET /luminix-api/users?where[name]=Jo%C3%A3o%20Silva

// Busca usuários cujo nome contém "João"
GET /luminix-api/users?where[name:contains]=Jo%C3%A3o

// Busca produtos com preço menor que 50
GET /luminix-api/products?where[price:lessThan]=50

// Busca usuários criados no ano de 2024
GET /luminix-api/users?where[created_at:between][]=2024-01-01&where[created_at:between][]=2024-12-31
```

Os operadores disponíveis são:

- `equals`: Filtra registros onde o valor da coluna é igual ao valor fornecido.
- `notEquals`: Filtra registros onde o valor da coluna é diferente do valor fornecido.
- `greaterThan`: Filtra registros onde o valor da coluna é maior que o valor fornecido.
- `greaterThanOrEquals`: Filtra registros onde o valor da coluna é maior ou igual ao valor fornecido.
- `lessThan`: Filtra registros onde o valor da coluna é menor que o valor fornecido.
- `lessThanOrEquals`: Filtra registros onde o valor da coluna é menor ou igual ao valor fornecido.
- `like`: Filtra registros onde o valor da coluna corresponde ao padrão fornecido. Este operador suporta o curinga `%`.
- `contains`: Filtra registros onde o valor da coluna contém o valor fornecido (equivalente a `%valor%`).
- `startsWith`: Filtra registros onde o valor da coluna começa com o valor fornecido (equivalente a `valor%`).
- `endsWith`: Filtra registros onde o valor da coluna termina com o valor fornecido (equivalente a `%valor`).
- `null`: Filtra registros onde o valor da coluna é `null`.
- `notNull`: Filtra registros onde o valor da coluna não é `null`.
- `relation`: Filtra registros por um modelo relacionado. O valor deve ser o ID do modelo relacionado.

Caso o operador seja `equals` ou `relation`, você pode omiti-lo da chave. Por exemplo, `where[age]=18` é equivalente a `where[age:equals]=18`.

É possível combinar múltiplas condições adicionando mais pares chave-valor ao parâmetro `where`. Por exemplo, para filtrar usuários por idade e status de verificação de e-mail:

```http
GET /luminix-api/users?where[age:greaterThan]=18&where[email_verified_at:notNull]=1
```

Entretanto, não é possível aplicar condições "OR" na mesma consulta. Seria necessário criar um endpoint personalizado ou um filtro customizado para esse cenário.

## Excluindo Colunas Sensíveis

Você pode excluir colunas específicas da filtragem definindo a chave de configuração `api.filter.exclude`. Isso é útil para evitar que clientes filtrem colunas sensíveis ou internas.

```php
// Arquivo de configuração do backend do Luminix
'api' => [
    'filter' => [
        'exclude' => [
            'App\Models\User:password,email_verified_at',
        ],
    ],
],
```

Isso impedirá que clientes filtrem pelas colunas `password` e `email_verified_at` do modelo `User`.

> **Nota:** Por padrão, as colunas definidas no array `$hidden` do modelo são excluídas da filtragem. Portanto, este exemplo seria quase sempre redundante. Porém, se não for possível adicionar colunas ao array `$hidden` do modelo, esta configuração pode ser usada como alternativa.

## Alterando o comportamento do filtro

Você pode alterar o comportamento padrão do sistema de filtragem sobrescrevendo o método `scopeWhereMatchesFilter` no seu modelo. Isso permite que você personalize como os filtros são aplicados, adicionando lógica adicional ou alterando a forma como os dados são recuperados.

```php
use Illuminate\Database\Eloquent\Builder;
use Luminix\Backend\Model\LuminixModel;
use Luminix\Backend\Services\ModelFilter;

class Match extends Model
{
    use LuminixModel;

    public function scopeWhereMatchesFilter(Builder $query, array $filters): Builder
    {
        if (isset($filters['team_id'])) {
            // Filtra partidas onde o time está envolvido
            $query->where(function ($subQuery) use ($filters) {
                $subQuery->where('home_team_id', $filters['team_id'])
                    ->orWhere('away_team_id', $filters['team_id']);
            });

            // Remove a chave `team_id` dos filtros para evitar conflitos
            // com outros filtros do Luminix
            unset($filters['team_id']);
        }

        // Aplica os outros filtros do Luminix
        // A omissão da linha abaixo fará com que o Luminix ignore outros parâmetros enviados no `where`
        (new ModelFilter(static::class, $query))->apply($filters);
    }
}
```

Desta forma é possível adicionar lógica de filtragem customizada ao seu modelo, permitindo que você crie filtros mais complexos e específicos para suas necessidades.

## Filtrando Relacionamentos

Para filtrar registros com base em relacionamentos, você pode usar o nome de um relacionamento como a coluna. O valor pode ser um ID ou um array de IDs do modelo relacionado. O Luminix irá aplicar a filtragem automaticamente.

```http
// Busca produtos relacionados a uma categoria específica
GET /luminix-api/products?where[category]=1
// Busca produtos relacionados a várias categorias
GET /luminix-api/products?where[category][]=1&where[category][]=2
```

## Registrando Operadores Personalizados

É possível adicionar operadores personalizados ao sistema de filtragem registrando macros na classe `Luminix\Backend\Services\ModelFilter`. Isso permite definir métodos de filtragem customizados que podem ser usados no parâmetro `where`.

Exemplo de registro de um operador personalizado:

```php
use Luminix\Backend\Services\ModelFilter;

// Registra um operador para busca geográfica aproximada
// considerando banco de dados MySQL com suporte a geolocalização
ModelFilter::macro('inRadius', function (Builder $query, string $column, array $coordinates) {
    [$latitude, $longitude, $radius] = $coordinates;
    
    return $query->whereRaw(
        "ST_Distance_Sphere({$column}, point(?, ?)) <= ?",
        [$longitude, $latitude, $radius * 1000]
    );
});
```

Com esta macro registrada, você pode usar o operador `inRadius` no parâmetro `where` para filtrar modelos que tenham uma coluna do tipo `POINT` (ou similar) e que suportem geolocalização:

```http
# Busca lojas em um raio de 5km de São Paulo (lat, long, raio_km)
GET /luminix-api/shops?where[location:inRadius][]=-23.550520&where[location:inRadius][]=-46.633308&where[location:inRadius][]=5
```
