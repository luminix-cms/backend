# Validação de Dados no Luminix Backend

## Introdução

A validação robusta de dados é essencial para manter a integridade e segurança das informações em sua API. O Luminix Backend oferece um sistema de validação flexível e poderoso que aproveita os recursos nativos de validação do Laravel, permitindo que você defina e aplique regras de validação de forma consistente em diferentes contextos.

---

## Abordagens de Validação

O Luminix Backend suporta dois métodos principais para definir regras de validação:

### 1. Validação Direta no Modelo

Você pode definir regras de validação diretamente no modelo sobrescrevendo o método `getValidationRules`. Essa abordagem é ideal para cenários mais simples.

```php
class User extends Model
{
    use LuminixModel;

    protected function getValidationRules(string $for): array
    {
        return match ($for) {
            // Regras específicas para criação de usuários
            'store' => [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ],
            // Regras diferentes para atualização de usuários
            'update' => [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $this->id,
                'password' => 'sometimes|string|min:8|confirmed',
            ],
            // Regras personalizadas para contextos específicos
            'profile_update' => [
                'bio' => 'nullable|string|max:500',
                'avatar' => 'sometimes|image|max:2048',
            ],
            default => [],
        };
    }
}
```

### Contextos de Validação

O parâmetro `$for` permite definir regras para diferentes cenários:
- `store`: Usado na operação padrão de criação (`store`) do controlador
- `update`: Usado na operação padrão de atualização (`update`) do controlador
- Contextos personalizados: Permite criar validações específicas. Para isso, chame o método `validateRequest` do modelo passando o contexto desejado.

**Exemplo de Uso:**
```php
$user->validateRequest($request, 'profile_update');
```

---

### 2. Classes de Validação Dedicadas

Para lógicas complexas ou melhor organização, utilize classes de validação dedicadas.

```php
use Luminix\Backend\Validation\WithValidator;
use App\Validators\UserValidator;

#[WithValidator(UserValidator::class)] // Atributo que vincula o validador ao modelo
class User extends Model
{
    use LuminixModel;
}
```

#### Criando uma Classe de Validação

```php
use Luminix\Backend\Validation\Validator;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserValidator extends Validator
{
    // Validação para atualização de usuário
    public function update(User $user)
    {
        return [
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes', 
                'email', 
                Rule::unique('users')->ignore($user->id) // Ignora o e-mail atual do usuário
            ],
            'password' => [
                'sometimes', 
                'string', 
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols() // Exige senha complexa
            ],
        ];
    }

    // Cada contexto de validação deve ter um método correspondente
    // A ausência do método remove a validação para aquele contexto
}
```

---

## Gerando Classes de Validação

Use o comando Artisan para criar rapidamente um validador:

```bash
php artisan make:validator UserValidator
```

> **Dica:** O comando gera a classe no diretório `app/Validators` com um *stub* básico.

---

## Tratamento de Erros de Validação

O Luminix Backend trata automaticamente os erros de validação, retornando uma resposta HTTP `422 Unprocessable Entity` com detalhes dos erros. Isso segue o padrão de APIs RESTful e é compatível com clientes frontend modernos.

**Exemplo de Resposta de Erro:**
```json
{
    "message": "O campo email é obrigatório.",
    "errors": {
        "email": ["O campo email é obrigatório."]
    }
}
```

---

## Boas Práticas e Dicas

1. **Separação de Responsabilidades**: Use classes dedicadas para validações complexas ou reutilizáveis.
2. **Teste de Contextos**: Valide diferentes cenários (criação, atualização, ações personalizadas) para garantir consistência.
3. **Regras Condicionais**: Aproveite regras como `sometimes` e `nullable` para flexibilidade.
4. **Segurança**: Sempre valide campos sensíveis (como senhas) com regras rigorosas (ex: `Password::min(8)->uncompromised()`).

Para personalizações avançadas, consulte a [documentação oficial do Laravel sobre validação](https://laravel.com/docs/validation).