# Laravel Gate Integration

Luminix provides robust security through Laravel's Gate system, ensuring that API access is tightly controlled and secure by default.

## Security Philosophy

By default, Luminix implements a "deny-first" approach to API access. This means:
- No API endpoint is accessible without explicit permission
- You have full control over who can access your data
- Security is enforced at the individual action level

## Configuring Gate Checks

### Enabling or Disabling Gate Checks

You can globally enable or disable Laravel Gate checks in your configuration:

```php
// config/luminix/backend.php
'security' => [
    'gates_enabled' => true, // Default is true
    // Set to false to disable all gate checks
],
```

### Defining Permission Requirements

Specify the permissions required for each standard API action:

```php
// config/luminix/backend.php
'security' => [
    'permissions' => [
        'index'   => 'read',    // Listing items
        'show'    => 'read',    // Viewing a single item
        'store'   => 'create',  // Creating new items
        'update'  => 'update',  // Modifying existing items
        'destroy' => 'delete',  // Removing items
    ]
]
```

## Understanding Permission Naming

Permissions are dynamically generated using a consistent naming pattern:
- Format: `{action}-{model_alias}`
- Examples:
  - Reading users: `read-user`
  - Creating todos: `create-to_do`
  - Updating posts: `update-post`

## Implementing Gate Definitions

Define your permissions in the `AuthServiceProvider`:

```php
// app/Providers/AuthServiceProvider.php
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\ToDo;

public function boot()
{
    // Only allow users to view their own profile
    Gate::define('read-user', function (?User $currentUser, User $targetUser) {
        return $currentUser && $currentUser->id === $targetUser->id;
    });

    // Allow any authenticated user to create todos
    Gate::define('create-to_do', function (?User $user) {
        return !!$user;
    });

    // Restrict todo updates to the todo's owner
    Gate::define('update-to_do', function (?User $user, ToDo $todo) {
        return $user && $user->id === $todo->user_id;
    });
}
```

## Important Considerations

### List Endpoint Behavior
- When retrieving a list of items, the gate is checked for **each individual item**
- A user must have permission for every item in the list
- If permission is denied for any item, the request is aborted with a Forbidden response

### Debugging Permissions
- Use Laravel's `Gate` facade methods like `allows()` and `denies()` to test permissions
- Check your application logs for detailed permission-related errors

## Best Practices
- Start with restrictive permissions
- Use the principle of least privilege
- Regularly audit and update your gate definitions
- Consider role-based access control for complex permission scenarios

## Next Steps
- [Explore Query-Level Permissions](3c-query-permissions.md)
- [Return to Documentation Index](0-index.md)