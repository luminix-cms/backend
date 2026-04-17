# Como adicionar a API REST

Para habilitar o Luminix Backend a gerar endpoints de API para seus modelos, você precisa adicionar a trait `LuminixModel` a cada classe de modelo que deseja expor.

```php
use Illuminate\Database\Eloquent\Model;
use Luminix\Backend\Model\LuminixModel;

class User extends Model
{
    use LuminixModel;
    
    // Seu código do modelo aqui
}
```

Após adicionar a trait, o Luminix detectará e processará automaticamente o modelo, gerando endpoints de API para ele.

## Requisitos do Modelo

Para garantir a geração correta da API, seus modelos devem atender aos seguintes requisitos:

1. **Namespace do Modelo**: Os modelos devem estar no namespace especificado no arquivo de configuração. Por padrão, o Luminix procura modelos no namespace `App\Models`.

2. **Uso da LuminixModel**: A trait `LuminixModel` deve ser incluída na classe do modelo. Essa trait fornece a funcionalidade necessária para a geração da API.

3. **Atributos Fillable**: Os modelos devem definir a propriedade `$fillable` para especificar quais atributos podem ser atribuídos em massa. Isso é importante para criar e atualizar recursos via API.

4. **Chave Primária**: Modelos sem chave primária não são suportados. A [chave primária do Eloquent](https://laravel.com/docs/11.x/eloquent#primary-keys) será usada para identificar os recursos.

## Apelidos de Modelo

Cada modelo terá um *apelido* gerado com base no nome da classe do modelo, sem o namespace. O apelido é usado na nomeação das rotas e na geração de URLs. Por padrão, o apelido é o nome da classe do modelo em snake case. Você pode personalizar o apelido definindo um método estático `getAlias` em seu modelo.

```php
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    public static function getAlias(): string
    {
        return 'my_user';
    }
}
```

As rotas serão nomeadas usando o alias. Por exemplo, a rota index para o modelo `User` será nomeada `luminix.my_user.index`. O alias será então pluralizado e "slugificado" para gerar a URL. Por exemplo, a URL base para o modelo `User` acima será `/luminix-api/my-users`.

### Nome de Exibição

Além do apelido, cada modelo expõe um nome de exibição legível via o método estático `getDisplayName()`, que retorna um array com as formas singular e plural do nome da classe:

```php
User::getDisplayName();
// ['singular' => 'User', 'plural' => 'Users']

ToDo::getDisplayName();
// ['singular' => 'To Do', 'plural' => 'To Dos']
```

Você pode sobrescrever esse método para retornar nomes localizados.

### Campo de Label

O método `getLabel()` retorna o nome do campo usado como rótulo do registro (útil no parâmetro `minified` e em selects). Por padrão, retorna o primeiro campo em `$fillable`. Para personalizar, defina a propriedade `$labeledBy` no modelo:

```php
class User extends Model
{
    use LuminixModel;

    protected string $labeledBy = 'name';
}
```

### Apelidos de Modelo em Relações Polimórficas

Por padrão, o Luminix Backend registrará [Tipos Polimórficos Personalizados](https://laravel.com/docs/11.x/eloquent-relationships#custom-polymorphic-types) para todos os modelos habilitados no Luminix. Isso significa que o apelido do modelo será usado como o tipo morph em relações polimórficas. Se você deseja evitar isso, chame o método `preventEnforcingMorphMap` no método `register` do seu service provider.

```php
// app/Providers/AppServiceProvider.php

use Luminix\Backend\BackendServiceProvider;

function register() {
    BackendServiceProvider::preventEnforcingMorphMap();
}
```
