# Automatic Endpoint Generation

## Overview

Luminix Backend simplifies API development by automatically generating RESTful endpoints for your Laravel models. This powerful feature allows developers to:

- Rapidly create standardized API interfaces
- Reduce boilerplate code
- Implement consistent security and filtering
- Easily customize and extend default behaviors

## Endpoint Naming Conventions

### URL Structure

All endpoints follow a consistent pattern:
- Prefix: `/luminix-api` (configurable)
- Model Representation: Pluralized and *slugified* model alias

#### Model Slug Transformation Examples

| Model Name | Model Alias | Model Slug |
|------------|-------------|------------|
| `App\Models\User` | `user` | `users` |
| `App\Models\ToDo` | `to_do` | `to-dos` |

> **Note**: *Slugification* converts underscores to hyphens and ensures URL-friendly naming.

## CRUD Operations

### Listing Records: `GET /{$prefix}/{$modelSlug}`

Retrieve a paginated list of model records with powerful filtering options.

#### Query Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `page` | Integer | Pagination page number | `?page=2` |
| `per_page` | Integer | Records per page | `?per_page=10` |
| `q` | String | Searches for the given term | `?q=John` |
| `order_by` | String | Sort results (`column:direction`) | `?order_by=email:desc` |
| `where` | Array | Advanced filtering | `?where[age:greaterThan]=18` |
| `tab` | String | Custom tab-based filtering | `?tab=active` |

#### Example Requests

```javascript
// Comprehensive filtering example
axios.get('/luminix-api/users', {
    params: {
        page: 2,
        per_page: 10,
        order_by: 'email:desc',
        where: {
            'name:contains': 'John',
            'age:between': [18, 30],
            'email_verified_at:notNull': 1
        }
    }
});
```

### Creating Records: `POST /{$prefix}/{$modelSlug}`

Create new model records with built-in attribute protection.

```javascript
axios.post('/luminix-api/to-dos', {
    title: 'Buy groceries',
    description: 'Milk, eggs, bread, and butter',
    due_date: '2024-12-31'
});
```

> **Tip**: Only fillable attributes will be set. Add validation rules for data integrity.

### Retrieving a Single Record: `GET /{$prefix}/{$modelSlug}/{{primary_key}}`

Fetch a specific record by its primary key.

```
GET /luminix-api/users/1
GET /luminix-api/to-dos/14
```

### Updating Records: `POST /{$prefix}/{$modelSlug}/{{primary_key}}`

Update existing records with optional restoration.

```javascript
axios.post('/luminix-api/to-dos/14', {
    title: 'Updated Grocery List'
});

// restore soft-deleted record
axios.post('/luminix-api/to-dos/14?restore=1');
```

### Deleting Records

#### Single Record: `DELETE /{$prefix}/{$modelSlug}/{{primary_key}}`

```
DELETE /luminix-api/users/1
DELETE /luminix-api/to-dos/14?force=1  // Permanent deletion
```

#### Multiple Records: `DELETE /{$prefix}/{$modelSlug}`

```javascript
axios.delete('/luminix-api/to-dos', {
    data: { ids: [14, 15, 16] }
});
```

## Relationship Management

Luminix supports advanced relationship operations for many-to-many relationships.

### Prerequisites

Define syncable relationships in your model:

```php
class User extends Model
{
    use LuminixModel;

    protected $syncs = ['roles'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}
```

### Relationship Operations

#### Sync Relationship: `POST /{$prefix}/{$modelSlug}/{{primary_key}}/{{relation}}/sync`

```javascript
// Simple sync
axios.post('/luminix-api/users/1/roles/sync', [1, 2, 3]);

// Sync with pivot data
axios.post('/luminix-api/users/1/roles/sync', [
    { id: 1, expires_at: '2024-12-31' }
]);
```

#### Attach Relationship: `POST /{$prefix}/{$modelSlug}/{{primary_key}}/{{relation}}/{{related_primary_key}}`

```javascript
// Simple attach
axios.post('/luminix-api/users/1/roles/4');

// Attach with pivot data
axios.post('/luminix-api/users/1/roles/4', {
    expires_at: '2024-12-31'
});
```

#### Detach Relationship: `DELETE /{$prefix}/{$modelSlug}/{{primary_key}}/{{relation}}/{{related_primary_key}}`

```
DELETE /luminix-api/users/1/roles/4
```

## Next Steps

- [Validation](2b-validation.md)
- [Back to Documentation Index](0-index.md)