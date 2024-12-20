# Query-Level Permissions

## Overview

Query-level permissions allow you to dynamically filter records based on user permissions directly at database level. This powerful feature enables fine-grained access control by eliminating unauthorized records before they are returned to the client.

## Purpose

The `scopeAllowed` method provides a mechanism to:
- Restrict access to records based on user-specific conditions
- Implement row-level security
- Prevent unauthorized users from accessing or modifying specific records

## Implementation

To implement query-level permissions, override the `scopeAllowed` method in your model with custom logic that filters records based on the user's permissions.

### Basic Example

```php
use Illuminate\Database\Eloquent\Builder;

class Post extends Model
{
    /**
     * Scope queries to only include records the user is allowed to access.
     *
     * @param Builder $query The Eloquent query builder
     * @param string $permission The type of permission being checked (e.g., 'read', 'update', 'delete')
     * @return void
     */
    public function scopeAllowed(Builder $query, string $permission)
    {
        // Only allow authors to update or delete their own posts
        if (in_array($permission, ['update', 'delete'])) {
            $query->where('author_id', auth()->id());
        }
        
        // Optionally add different logic for different permissions
        if ($permission === 'read') {
            // Example: Allow viewing of published posts or user's own drafts
            $query->where(function ($q) {
                $q->where('status', 'published')
                   ->orWhere('author_id', auth()->id());
            });
        }
    }
}
```

## Key Concepts

### Permission Types
The `scopeAllowed` method will handle all permissions mapped in the `security.permissions` configuration. The default possible values are `read`, `create`, `update`, and `delete`.

### Authentication
To utilize `auth()->id()` to get the current user's ID, ensure you have proper authentication middleware in place.

### Behavior
- If no records match the allowed conditions, an empty result set is returned
- Follows the `404 Not Found` pattern for unauthorized access attempts

## Advanced Use Cases

### Multiple Conditions
You can implement complex permission logic with multiple conditions:

```php
public function scopeAllowed(Builder $query, string $permission)
{
    if ($permission === 'update') {
        $query->where(function ($q) {
            $q->where('author_id', auth()->id())
              ->orWhere('team_id', auth()->user()->team_id)
              ->orWhereHas('collaborators', function ($subQuery) {
                  $subQuery->where('user_id', auth()->id());
              });
        });
    }
}
```

### Role-Based Permissions
Integrate with role-based access control systems:

```php
public function scopeAllowed(Builder $query, string $permission)
{
    if (auth()->user()->hasRole('admin')) {
        // Admins can access everything
        return;
    }
    
    // Apply specific permissions for non-admin users
    $query->where('organization_id', auth()->user()->organization_id);
}
```

## Best Practices

- Keep permission logic clear and concise
- Use existing authentication and authorization mechanisms
- Test permission scenarios thoroughly
- Consider performance implications of complex query scopes

## Potential Pitfalls

- Ensure consistent application of permission logic across different query methods
- Be cautious of N+1 query problems when using complex relationship-based permissions
- Always validate permissions at both the query and application logic levels

## Next Steps

[Eager Loading Relationships](3d-eager-loading.md)
[Back to Documentation Index](0-index.md)
