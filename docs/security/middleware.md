# Middleware Configuration

The middleware configuration is a crucial part of the Luminix Backend's security setup. Middleware provides an additional layer of security and authentication for your API routes, ensuring that only authorized requests are processed.

## Security Configuration

### Global Middleware

| Key | Default Value | Description |
|-----|--------------|-------------|
| `security.middleware` | `['api', 'auth']` | Middleware applied to all Luminix API routes. This ensures that all API requests pass through the specified middleware stack, providing essential security and authentication checks. |

By default, Luminix applies the `api` and `auth` middleware to all API routes. The `api` middleware group typically includes rate limiting and other API-specific middleware, while the `auth` middleware ensures that only authenticated users can access the API.

You can customize the middleware stack by modifying the `security.middleware` configuration in the `config/luminix/backend.php` file. For example, you might want to add additional middleware for logging, throttling, or custom authentication mechanisms.

```php
return [
    'security' => [
        'middleware' => ['api', 'auth', 'custom-middleware'],
    ],
];
```

### Endpoint Middleware

It is possible to set the middleware for a specific route. This is achieved by [customizing the model actions](../basics/customize-endpoints.md#add-custom-actions).

```php
use Luminix\Backend\Services\RouteGenerator;

public function register()
{
    RouteGenerator::reducer('modelUserRoutes', function ($routes) {
        $routes['index']['middleware'] = ['custom-middleware'];

        return $routes;
    });
}

```
