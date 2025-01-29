# Tab Based Filtering

Luminix allows the creation of "tabs" to filter data based on specific conditions. This feature is useful when you want to provide users with predefined filters for common use cases. By default, models with `SoftDeletes` have a "trashed" tab to retrieve only soft-deleted records.

```http
GET /luminix-api/users?tab=trashed
```

## Registering Tabs

To define custom tabs, you need to override the `scopeWhereBelongsToTab` method in your model. This method should handle the logic to filter records based on the tab name, including the "trashed" tab if you want to keep it.

```php
use Illuminate\Database\Eloquent\Builder;

class User extends Model
{
    /**
     * Scope queries to only include records that belong to the specified tab.
     *
     * @param Builder $query The current Eloquent query builder
     * @param string $tab The name of the tab being filtered
     * @return void|Builder
     */
    public function scopeWhereBelongsToTab(Builder $query, string $tab)
    {
        return match ($tab) {
            'trashed' => $query->onlyTrashed(),
            'active' => $query->where('status', 'active'),
            default => $query,
        };
    }
}
```

## Key Concepts

### Default Tab


