# Respostas

Em alguns cenários, você pode querer personalizar o formato das respostas dos seus endpoints API. Em muitos casos, isso pode ser alcançado [utilizando comportamentos do modelo](../basics/customize-endpoints.md#adicionar-comportamentos-ao-próprio-modelo), como ocultar atributos ou [carregamento antecipado de relacionamentos](../eager-loading.md). Porém, em situações específicas, você pode precisar de um controle mais granular sobre o formato da resposta.

O Luminix permite que você registre uma [Laravel Resource](https://laravel.com/docs/11.x/eloquent-resources) para transformar os dados antes de enviá-los ao cliente.

## Registrando um Resource

Para personalizar o formato da resposta, crie uma nova classe Resource usando o comando `artisan`:

```bash
php artisan make:resource UserResource
```

Siga a [documentação do Laravel](https://laravel.com/docs/11.x/eloquent-resources) para definir a estrutura da sua Resource. Você pode personalizar os dados, incluir metadados adicionais e formatar a resposta conforme necessário.

Para aplicar a Resource aos endpoints da API, adicione o atributo `Luminix\Backend\Resources\WithResource` ao seu modelo, especificando a classe Resource como argumento:

```php
use Illuminate\Database\Eloquent\Model;
use Luminix\Backend\Model\LuminixModel;
use Luminix\Backend\Resources\WithResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserCollection;

#[WithResource(single: UserResource::class, collection: UserCollection::class)]
class User extends Model
{
    use LuminixModel;
}
```

Neste exemplo:
- `UserResource` é usado para respostas de registros individuais
- `UserCollection` é usado para respostas paginadas

**Funcionalidades avançadas:**
1. Transformação condicional de dados usando métodos `when` e `whenLoaded`
2. Inclusão de relacionamentos via `->load()` no controller
3. Formatação consistente de datas e valores numéricos
4. Adição de metadados personalizados na resposta

**Exemplo de Resource personalizada:**
```php
// UserResource.php
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome_completo' => $this->first_name . ' ' . $this->last_name,
            'email' => $this->when($request->user()->isAdmin(), $this->email),
            'ultimo_acesso' => $this->last_login?->format('d/m/Y H:i'),
            'perfis' => ProfileResource::collection($this->whenLoaded('profiles'))
        ];
    }
}
```

> **Dica:** Utilize Resources para:
> - Garantir consistência nas respostas da API
> - Implementar controle de acesso granular a campos sensíveis
> - Reduzir payloads desnecessários
> - Formatarcampos complexos (datas, valores monetários, etc.)