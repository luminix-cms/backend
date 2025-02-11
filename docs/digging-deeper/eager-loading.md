# Eager Loading Relationships in Luminix

Eager loading is a crucial technique in Laravel for optimizing database queries by loading related models efficiently. In Luminix, developers have flexible control over how relationships are loaded, preventing the need for multiple API requests.

## Understanding Eager Loading

Eager loading allows you to load related models alongside the primary model, reducing the number of database queries and preventing the N+1 query problem. Luminix provides multiple approaches to add eager loaded relationships to your API responses.

## 1. Global Relationship Loading with `$with` Property

The `$with` property provides a simple way to always eager load specific relationships for a model. This is ideal for relationships that are consistently needed.

```php
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    // Always load roles whenever a User is queried
    protected $with = ['roles'];

    public function roles()
    {
        return $this->hasMany(Role::class);
    }
}
```

### Considerations
- ⚠️ Use with caution: This approach **always** loads the specified relationships, unless specified otherwise in the query.
- Risk of creating recursive loading if relationships are interdependent

## 2. Dynamic Relationship Loading with Query Scopes

For more granular control, override the `scopeBeforeLuminix` or `scopeAfterLuminix` methods to dynamically load relationships based on request parameters.

```php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Luminix\Backend\Model\LuminixModel;

class User extends Model
{
    use LuminixModel;

    public function scopeBeforeLuminix(Builder $query, Request $request)
    {
        // List of allowed relationships
        $allowedRelationships = ['roles', 'permissions'];

        // Check if 'with' parameter is present in the request
        if ($request->has('with')) {
            $requested = $request->input('with', []);
            $with = collect(is_string($requested) ? [$requested] : $requested);

            if (!$with->every(fn ($relation) => in_array($relation, $allowedRelationships))) {
                // Load only allowed relationships
                abort(422, 'Invalid "with" parameter');
            }

            // Load 'roles' relationship if requested
            if ($with->contains('roles')) {
                // You can add a closure to modify the sub-query
                $query->with([
                    // In this example, we will use the 'allowed'
                    // scope to load only roles that the user is 
                    // allowed to read, considering the Role model
                    // is also using LuminixModel and has query-level
                    // permissions implemented
                    'roles' => fn ($query) => $query->allowed('read')
                ]);
            }

            // ... Load other relationships as needed

        }
    }

}
```

### API Request Examples

Load specific relationships dynamically:
- `/luminix-api/users?with=roles`
- `/luminix-api/users/1?with[]=roles&with[]=permissions`

### Benefits of This Approach
- Fine-grained control over relationship loading
- Flexibility for API consumers

## Best Practices

1. **List Allowed Relationships**: Always define an explicit list of relationships that can be eager loaded.
2. **Consider Performance**: Be mindful of loading large or complex relationships. Consider limiting the number of records loaded through eager loading if necessary.
3. **Use Conditional Loading**: Implement logic to load relationships only when necessary.
4. **Secure Related Data**: Ensure that no sensitive data is exposed through eager loading.
