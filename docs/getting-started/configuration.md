# Luminix Backend Configuration Reference

To publish the configuration file, use the command below:

```bash
php artisan vendor:publish --tag=luminix-config
```

## Model Discovery Configuration

| Key | Default Value | Description |
|-----|--------------|-------------|
| `models.namespace` | `'App\Models'` | The default namespace where Luminix will discover and scan for models. All models in this namespace that use the `LuminixModel` trait will be processed. |
| `models.include` | `[]` | An array of additional model classes that can be manually included for Luminix processing. Useful for adding models from different namespaces or third-party packages. |

## API Configuration

| Key | Default Value | Description |
|-----|--------------|-------------|
| `api.prefix` | `'luminix-api'` | The URL prefix for all Luminix API routes. This allows you to customize the base endpoint for your API. |
| `api.max_per_page` | `150` | Maximum number of items that can be returned in a single API request. Helps prevent excessive data retrieval and improves performance. |
| `api.controller` | `'Luminix\Backend\Controllers\ResourceController'` | The default controller used for handling Luminix API routes. Changing this will affect the behavior of all API endpoints. |
| `api.controller_overrides` | `[]` | A mapping of model classes to custom controller classes. Allows individual model-specific API handling. |

## API Filtering Configuration

| Key | Default Value | Description |
|-----|--------------|-------------|
| `api.filter.enable` | `true` | Enables or disables API filtering functionality. When enabled, clients can filter API results using the 'where' parameter. |
| `api.filter.exclude` | `[]` | A list of columns to exclude from filtering for specific models. Prevents filtering on sensitive or internal columns. Format is `'ModelClassName:column1,column2'`. By default the model's `hidden` columns are excluded. |
| `api.filter.throw` | `true` | Determines whether Luminix should throw exceptions when filtering fails (e.g., invalid column or operator). Helps identify and debug filtering issues. |

## Security Configuration

| Key | Default Value | Description |
|-----|--------------|-------------|
| `security.gates_enabled` | `true` | Enables Laravel Gate checks for route-level permissions. When active, Luminix enforces permissions defined in the `permissions` section. |
| `security.middleware` | `['api', 'auth']` | Middleware applied to all Luminix API routes. Provides an additional layer of security and authentication. |
| `security.permissions` | See below | Maps controller actions to specific permission types. Enables fine-grained access control. |

### Detailed Permissions Mapping

| Action | Permission | Description |
|--------|------------|-------------|
| `index` | `'read'` | Permission required to list/retrieve multiple resources |
| `show` | `'read'` | Permission required to retrieve a single resource |
| `store` | `'create'` | Permission required to create a new resource |
| `update` | `'update'` | Permission required to modify an existing resource |
| `destroy` | `'delete'` | Permission required to delete a single resource |
| `destroyMany` | `'delete'` | Permission required to delete multiple resources |
| `restoreMany` | `'update'` | Permission required to restore multiple resources |
| `sync` | `'update'` | Permission required to synchronize resources |
| `attach` | `'update'` | Permission required to attach related resources |
| `detach` | `'update'` | Permission required to detach related resources |

These permissions are concatenated with the model name to form the final permission string. For example, the permission for the `index` action on the `User` model would be `'read-user'`.
