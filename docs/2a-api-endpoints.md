# Automatic Endpoint Generation

Luminix Backend automatically generates RESTful API endpoints for your models, allowing you to interact with your data through a standardized interface. By adding the `LuminixModel` trait to your models, you enable Luminix to create the necessary routes to handle CRUD operations. Of course, you can customize and extend these endpoints as needed.

## API Endpoints

The API is based on the following conventions:

 - All endpoints are prefixed with `/luminix-api`, unless changed in the `api.prefix` configuration. To refer to this segment, we will use the placeholder `{$prefix}`.
 - The model is represented by the pluralized and *slugified* [model alias](/docs/XX-models.md#model-alias). To refer to this segment, we will use the placeholder `{$modelSlug}`.

Examples:

| Model Name | Model Alias | Model Slug |
|------------|-------------|------------|
| `App\Models\User` | `user` | `users` |
| `App\Models\ToDo` | `to_do` | `to-dos` |

### Basic CRUD Operations

#### `GET /{$prefix}/{$modelSlug}`

Returns a paginated list of all records for the model.

 - **Action:** `index`
 - **Route Name:** `luminix.{$modelAlias}.index`
 - **Query Parameters:**
   - `page` (integer): The page number to retrieve.
   - `per_page` (integer): The number of records per page. Should be less than or equal to the `max_per_page` configuration value.
   - `q` (string): A search query to filter records by. Searches all fillable columns by default.
   - `order_by` (string): The column to order results by. The value should be in the format `column:direction`, where `direction` is either `asc` or `desc`.
   - `where` (array): The filter criteria to apply on the database query. Please refer to the [Filtering Data](/docs/XX-filtering.md#filter-syntax) section for more information.
   - `tab` (string): If the model implements the `scopeWhereBelongsToTab` method, this parameter can be used to filter records by a specific "tab".

Examples:
    
```
# Get the first page of users with 10 records
GET /luminix-api/users?page=1&per_page=10

# Get the users with `is_admin` set to true
GET /luminix-api/users?where[is_admin]=1

# Search for users with the name "John"
GET /luminix-api/users?q=John

# Order users by their email address in descending order
GET /luminix-api/users?order_by=email:desc

# Filter users by their age being greater than 18
GET /luminix-api/users?where[age:greaterThan]=18

# Filter users by range of age and verified email
GET /luminix-api/users?where[age:between][]=18&where[age:between][]=30&where[email_verified_at:notNull]=1

# Filter to dos by the user with ID 1, assuming the ToDo model has a `user` relationship
GET /luminix-api/to-dos?where[user]=1
```

This is how you can make a request using Axios combining many conditions:

```js
axios.get('/luminix-api/users', {
    params: {
        page: 1,
        per_page: 10,
        order_by: 'email:desc'
        where: {
            'name:contains': 'John',
            'age:between': [18, 30],
            'email_verified_at:notNull': 1
        }
    }
}).then(response => {
    console.log(response.data);
}).catch(error => {
    console.error(error);
});
```

#### `POST /{$prefix}/{$modelSlug}`

Creates a new record for the model.

 - **Action:** `store`
 - **Route Name:** `luminix.{$modelAlias}.store`
 - **Request Body:** The attributes of the new record. The object sent will be passed to the model's `fill` method, which means only fillable attributes will be set. You can add [data validation](/docs/2b-validation.md) rules to your models to ensure data integrity and/or [add a custom controller](/docs/XX-custom-controllers.md) to implement more complex rules into the `store` action.

Examples:

This is how you can create a `ToDo` record using Axios:

```js
axios.post('/luminix-api/to-dos', {
    title: 'Buy groceries',
    description: 'Milk, eggs, bread, and butter',
    due_date: '2024-12-31'
}).then(response => {
    console.log(response.data);
}).catch(error => {
    console.error(error);
});
```

#### `GET /{$prefix}/{$modelSlug}/{{primary_key}}`

Returns the record with the specified primary key.

 - **Action:** `show`
 - **Route Name:** `luminix.{$modelAlias}.show`

Examples:

```
# Get the user with ID 1
GET /luminix-api/users/1

# Get the to do with ID 14
GET /luminix-api/to-dos/14
```

#### `POST /{$prefix}/{$modelSlug}/{{primary_key}}`

Updates the record with the specified primary key.

 - **Action:** `update`
 - **Route Name:** `luminix.{$modelAlias}.update`
 - **Query Parameters:**
    - `restore` (boolean): If set, the record will be restored if it is soft-deleted.
 - **Request Body:** The attributes to update. The object sent will be passed to the model's `fill` method, which means only fillable attributes will be set. You can add [data validation](/docs/2b-validation.md) rules to your models to ensure data integrity and/or [add a custom controller](/docs/XX-custom-controllers.md) to implement more complex rules into the `update` action.

Examples:

This is how you can update a `ToDo` record using Axios:

```js
axios.post('/luminix-api/to-dos/14', {
    title: 'Buy groceries edited'
}).then(response => {
    console.log(response.data);
}).catch(error => {
    console.error(error);
});
```
 
#### `DELETE /{$prefix}/{$modelSlug}/{{primary_key}}`

Deletes the record with the specified primary key.

 - **Action:** `destroy`
 - **Route Name:** `luminix.{$modelAlias}.destroy`
 - **Query Parameters:**
   - `force` (boolean): If set, the record will be permanently deleted. Otherwise, it will be soft-deleted if the model uses the `SoftDeletes` trait.

Examples:

```
# Delete the user with ID 1
DELETE /luminix-api/users/1

# Permanently delete the to do with ID 14
DELETE /luminix-api/to-dos/14?force=1
```

#### `DELETE /{$prefix}/{$modelSlug}`

Deletes multiple records for the model.

 - **Action:** `destroyMany`
 - **Route Name:** `luminix.{$modelAlias}.destroyMany`
 - **Request Body:** A JSON with an array "ids" containing the primary keys of the records to delete.

Examples:

This is how you can delete multiple `ToDo` records using Axios:

```js
axios.delete('/luminix-api/to-dos', {
    data: {
        ids: [14, 15, 16]
    }
}).then(response => {
    console.log(response.data);
}).catch(error => {
    console.error(error);
});
```

#### `POST /{$prefix}/{$modelSlug}/restore`

Restores multiple soft-deleted records for the model.

 - **Action:** `restoreMany`
 - **Route Name:** `luminix.{$modelAlias}.restoreMany`
 - **Request Body:** A JSON with an array "ids" containing the primary keys of the records to restore.

Examples:

This is how you can restore multiple `ToDo` records using Axios:

```js
axios.post('/luminix-api/to-dos/restore', {
    ids: [14, 15, 16]
}).then(response => {
    console.log(response.data);
}).catch(error => {
    console.error(error);
});
```

### Relationships

Luminix API also handles many-to-many relationships, allowing you to sync, attach and detach related records. To enable these operations, you must define the relationship in your model and add the `$syncs` property to the model class.

```php
class User extends Model
{
    use LuminixModel;

    protected $syncs = ['roles']; // Define the relationships that can be managed via API

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

}
```

#### `POST /{$prefix}/{$modelSlug}/{{primary_key}}/{{relation}}/sync`

Synchronizes the related records for the given relationship.

 - **Action:** `sync`
 - **Route Name:** `luminix.{$modelAlias}.{$relation}:sync`
 - **Request Body:** An array containing the primary keys of the related records to sync, or an array of objects with the primary key and additional pivot data.

Examples:

```js
// Sync roles 1, 2, and 3 to user 1
axios.post('/luminix-api/users/1/roles/sync', [1, 2, 3]).then(response => {
    console.log(response.data);
}).catch(error => {
    console.error(error);
});

// Sync role 1 with additional pivot data to user 1
axios.post('/luminix-api/users/1/roles/sync', [{id: 1, expires_at: '2024-12-31'}])
    .then(response => {
        console.log(response.data);
    })
    .catch(error => {
        console.error(error);
    });
```

#### `POST /{$prefix}/{$modelSlug}/{{primary_key}}/{{relation}}/{{related_primary_key}}`

Attaches a related record to the given relationship.

 - **Action:** `attach`
 - **Route Name:** `luminix.{$modelAlias}.{$relation}:attach`
 - **Request Body:** An array of additional pivot data.

Examples:

```js
// Attach role 4 to user 1
axios.post('/luminix-api/users/1/roles/4').then(response => {
    console.log(response.data);
}).catch(error => {
    console.error(error);
});

// Attach role 4 with additional pivot data to user 1
axios.post('/luminix-api/users/1/roles/4', {expires_at: '2024-12-31'}).then(response => {
    console.log(response.data);
}).catch(error => {
    console.error(error);
});
```

#### `DELETE /{$prefix}/{$modelSlug}/{{primary_key}}/{{relation}}/{{related_primary_key}}`

Detaches a related record from the given relationship.

 - **Action:** `detach`
 - **Route Name:** `luminix.{$modelAlias}.{$relation}:detach`

Examples:

```
# Detach role 4 from user 1
DELETE /luminix-api/users/1/roles/4
```

## Next Steps

- [Validation](2b-validation.md)
- [Back to Documentation Index](0-index.md)
