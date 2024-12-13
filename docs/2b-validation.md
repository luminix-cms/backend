# Data Validation

Luminix API provides a simple way to validate incoming data using Laravel's built-in validation system. This allows you to ensure that the data you receive is in the correct format and meets your application's requirements.

## Defining Validation Rules

Validation rules can be defined by overriding the `getValidationRules` method in your model. It receives one string argument `$for`, which indicates the context in which the validation rules are being requested. Typicially would be `'store'` or `'update'`, but you can create custom contexts as needed. This method should return an array of validation rules that will be passed on to [Laravel's Validator](https://laravel.com/docs/11.x/validation).

Here's an example of how you might define validation rules for a `User` model:

```php
use Luminix\LuminixModel;

class User
{
    use LuminixModel;

    protected function getValidationRules($for)
    {
        return match ($for) {
            'store' => [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'password' => 'required|string|min:8',
            ],
            'update' => [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $this->id,
                'password' => 'sometimes|string|min:8',
            ],
            default => [],
        };
    }
}
```

## Validator Class

It is possible to create a class for validation rules and use it in the model. This is a way to keep the model clean and organized. The class must have the `\Luminix\Backend\Validation\ValidatedBy` attribute, which receives the model class as an argument. In order to use this feature, the model must **not** have the `getValidationRules` method implemented.

```php
use Luminix\Backend\Validation\ValidatedBy;
use App\Validators\UserValidator;

#[ValidatedBy(UserValidator::class)]
class User
{
    use LuminixModel;
}
```

The `UserValidator` class should extend the `Luminix\Backend\Validation\Validator` class and implement the methods for each context you want to validate. The method name should be the same as the context name.

```php
use Luminix\Backend\Validation\Validator;
use App\Models\User;

class UserValidator extends Validator
{
    public function store()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
        ];
    }

    public function update(User $user)
    {
        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:8',
        ];
    }
}
```

To make this process easier, you can use the `php artisan make:validator` command to generate a new validator class.

```bash
php artisan make:validator UserValidator
```


