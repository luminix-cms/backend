<?php

/*
|--------------------------------------------------------------------------
| Luminix Package Configuration
|--------------------------------------------------------------------------
|
| This configuration file is designed to provide detailed control over the Luminix package's
| features and behavior within your application. It covers various aspects such as initialization,
| routing, and model interactions. Each setting includes a clear explanation, ensuring you can
| tailor the package to meet your application's specific needs.
|
*/
return [

    /*
    |--------------------------------------------------------------------------
    | Luminix Model Discovery
    |--------------------------------------------------------------------------
    |
    | This section configures Luminix's model discovery behavior, including the namespace
    | for model discovery and the ability to include individual models, typically for 3rd 
    | party models or models not in the default namespace.
    |
    */
    'models' => [
        'namespace' => null, 
        'include' => [
            'Workbench\App\Models\User',
            'Workbench\App\Models\ToDo',

        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | Luminix API Configuration
    |--------------------------------------------------------------------------
    |
    | This section configures Luminix's routing behavior. It includes settings for route prefixes
    | and the default controller for handling Luminix routes. You can also override the default
    | controller for specific models using the 'controller_overrides' setting.
    |
    */
    'api' => [

        'prefix' => 'luminix-api',
        'max_per_page' => 150,

        /*
        |--------------------------------------------------------------------------
        | Controller Assignment
        |--------------------------------------------------------------------------
        |
        | 'controller' specifies the default controller for handling Luminix routes,
        | applicable to all models unless overridden in the 'controller_overrides'
        | setting.
        |
        */
        'controller' => 'Luminix\Backend\Controllers\ResourceController',
        'controller_overrides' => [
            // 'App\Models\User' => 'App\Http\Controllers\UserController',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Luminix Security Configuration
    |--------------------------------------------------------------------------
    |
    | This section configures Luminix's security features, including the ability to enable
    | route-level permissions and global middleware. You can also specify the middleware
    | for Luminix's API routes and associate controller actions with specific permissions.
    |
    */
    'security' => [

        /*
        |--------------------------------------------------------------------------
        | Enable Laravel Gate Checks
        |--------------------------------------------------------------------------
        |
        | 'gates_enabled' specifies whether Luminix should enforce Laravel Gate checks
        | for route-level permissions. When enabled, Luminix will use the 'permissions'
        | setting to enforce permissions for each controller action.
        |
        */
        'gates_enabled' => false,


        /*
        |--------------------------------------------------------------------------
        | API Route Middleware
        |--------------------------------------------------------------------------
        |
        | 'api' middleware applies to all Luminix API routes, reinforcing secure and
        | efficient interactions with Luminix's API endpoints.
        |
        */
        'middleware' => ['web', 'auth'],// ['auth:web'],

        /*
        |--------------------------------------------------------------------------
        | Route-Level Permissions Management
        |--------------------------------------------------------------------------
        |
        | 'permissions' associates controller actions with specific permissions. 
        | Permissions are combined with model names (e.g., 'read-user'). Absence 
        | of a permission disables the `Gate` check and the `scopeAllowed` query
        | scope.
        |
        */
        'permissions' => [
            'index' => 'read',
            'show' => 'read',
            'store' => 'create',
            'update' => 'update',
            'destroy' => 'delete',
            'destroyMany' => 'delete',
            'restoreMany' => 'update',
            'import' => 'create',
            'export' => 'read',
        ],

    ],

];
