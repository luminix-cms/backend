# Search

The API endpoints for paginated responses accept the `q` query parameter to search for records:

```
GET /luminix-api/users?q=John
```

The built-in search functionality in Luminix is a basic `LIKE` query that searches for the given query string in all fillable attributes of the model. You can customize this behavior by overriding the `scopeSearch` method in your model.

```php
use Illuminate\Database\Eloquent\Builder;

class User extends Model
{
    public function scopeSearch(Builder $query, string $search)
    {
        foreach (['name', 'email'] as $searchable) {
            $query->orWhere(
                $searchable,
                'like',
                '%' . $search . '%'
            );
        }
    }
}
```

## Integration with Laravel Scout

An integration with Laravel Scout will be considered in a future release. This will allow you to use the full power of Laravel Scout for searching your models.

