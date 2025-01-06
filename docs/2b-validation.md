# Data Validation in Luminix Backend

## Introduction

Robust data validation is crucial for maintaining data integrity and security in your API. Luminix Backend provides a flexible and powerful validation system that leverages Laravel's built-in validation capabilities, allowing you to easily define and enforce data validation rules across different contexts.

## Validation Approaches

Luminix Backend supports two primary methods of defining validation rules:

### 1. Inline Model Validation

You can define validation rules directly within your model by overriding the `getValidationRules` method. This approach is straightforward and works well for simpler validation scenarios.

```php
class User extends Model
{
    use LuminixModel;

    protected function getValidationRules(string $for): array
    {
        return match ($for) {
            // Rules specifically for creating a new user
            'store' => [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ],
            // Different rules for updating an existing user
            'update' => [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $this->id,
                'password' => 'sometimes|string|min:8|confirmed',
            ],
            // Separate rules for custom contexts
            'profile_update' => [
                'bio' => 'nullable|string|max:500',
                'avatar' => 'sometimes|image|max:2048',
            ],
            default => [],
        };
    }
}
```

### Validation Contexts

The `$for` parameter allows you to define different validation rules for various scenarios:
- `store`: Used in the default `store` implementation of the controller
- `update`: Used in the default `update` implementation of the controller
- Custom contexts: It is possible to create custom contexts. To do so, the user will need to validate the request by calling the `validateRequest` method from the model, passing the context as second argument.

```php
$user->validateRequest($request, 'profile_update');
```

### 2. Dedicated Validator Classes

For more complex validation logic or better separation of concerns, you can use a dedicated validator class.

```php
use Luminix\Backend\Validation\WithValidator;
use App\Validators\UserValidator;

#[WithValidator(UserValidator::class)]
class User extends Model
{
    use LuminixModel;
}
```

#### Creating a Validator Class

```php
use Luminix\Backend\Validation\Validator;
use App\Models\User;

class UserValidator extends Validator
{
    // Validation method updating a user
    public function update(User $user)
    {
        return [
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes', 
                'email', 
                Rule::unique('users')->ignore($user->id)
            ],
            'password' => [
                'sometimes', 
                'string', 
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
            ],
        ];
    }

    // Each validation context should have a corresponding method
    // The absence of a method will result in an empty validation array
}
```

## Generating Validator Classes

Use the Artisan command to quickly generate a validator:

```bash
php artisan make:validator UserValidator
```

## Handling Validation Errors

Luminix Backend automatically handles validation errors, returning a 422 Unprocessable Entity response with detailed error messages.

## Next Steps

- [Customize API Behavior](2c-customize-api-behavior.md)
- [Back to Documentation Index](0-index.md)
