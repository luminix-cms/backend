<?php

/*
|--------------------------------------------------------------------------
| Luminix Package Comprehensive Configuration
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
    | Luminix Initialization Configuration
    |--------------------------------------------------------------------------
    |
    | The 'boot' section defines how Luminix initializes within your application. This includes
    | settings for the boot method, manifest data inclusion, and other key startup configurations.
    | These options are critical for a seamless integration of Luminix with your application's framework.
    |
    */
    'boot' => [

        /*
        |--------------------------------------------------------------------------
        | Boot Method Options
        |--------------------------------------------------------------------------
        |
        | 'method' specifies the initialization approach for Luminix. Available options:
        |
        | 'api': Initializes Luminix through an API call, suitable for SPAs or decoupled architectures.
        | 'embed': Embeds boot data in HTML, ideal for traditional server-rendered applications.
        |
        | This setting can be dynamically overridden with the LUMINIX_BOOT_METHOD environment variable.
        |
        */
        'method' => env('LUMINIX_BOOT_METHOD', 'api'),

        /*
        |--------------------------------------------------------------------------
        | Manifest Data Inclusion Setting
        |--------------------------------------------------------------------------
        |
        | Determines if manifest data, including routes and models, is included during boot.
        | Set to true for automatic inclusion or false for manual generation via artisan commands.
        |
        | Optimizes boot payload size and controls data exposure for enhanced performance and security.
        |
        */
        'includes_manifest_data' => env('LUMINIX_BOOT_INCLUDES_MANIFEST_DATA', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Luminix Routing Management
    |--------------------------------------------------------------------------
    |
    | This section configures Luminix's routing behavior. It includes settings for route prefixes,
    | middleware application, route exclusions, and model controller overrides. These configurations
    | play a vital role in managing routing paths and ensuring the security of your application.
    |
    */
    'routing' => [

        /*
        |--------------------------------------------------------------------------
        | URL Prefix for Luminix Routes
        |--------------------------------------------------------------------------
        |
        | 'prefix' configures a URL prefix for Luminix routes, helping to prevent route conflicts and
        | align with your application's URL structure.
        |
        | For example, 'prefix' => 'luminix' means routes will start with 'yourapp.com/api/luminix/...'.
        |
        */
        'prefix' => 'luminix',

        /*
        |--------------------------------------------------------------------------
        | Default Controller Assignment
        |--------------------------------------------------------------------------
        |
        | 'controller' specifies the default controller for handling Luminix routes, applicable
        | to all models unless overridden in the 'controller_overrides' setting.
        |
        */
        'controller' => 'Luminix\Backend\Controllers\ResourceController',

        /*
        |--------------------------------------------------------------------------
        | Maximum Items Per Page
        |--------------------------------------------------------------------------
        |
        | 'max_per_page' sets the maximum number of items per page for paginated responses.
        | This setting helps to manage the size of paginated data and optimize performance.
        |
        */
        'max_per_page' => 150,

        /*
        |--------------------------------------------------------------------------
        | Route-Level Permissions Management
        |--------------------------------------------------------------------------
        |
        | 'permissions' associates controller actions with specific permissions, enforced
        | via the `can` middleware. Permissions are combined with model names (e.g., 'read-user').
        | Absence of a permission disables the middleware application, deferring security to
        | the `routing.middleware.api` configuration.
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

        /*
        |--------------------------------------------------------------------------
        | Model-Specific Controller Overrides
        |--------------------------------------------------------------------------
        |
        | Facilitates custom controller assignment for specific models, allowing for
        | bespoke routing logic diverging from Luminix's standard approach.
        |
        | Format: 'ModelClassName' => 'ControllerClassName'.
        | Example: 'App\Models\User' => 'App\Http\Controllers\UserController'.
        |
        */
        'controller_overrides' => [
            // 'App\Models\User' => 'App\Http\Controllers\UserController',
        ],
        
        /*
        |--------------------------------------------------------------------------
        | Excluding Routes from Luminix Manifest
        |--------------------------------------------------------------------------
        |
        | Defines specific route names to be omitted from the Luminix route manifest.
        | Excluded routes will not be accessible via Luminix's frontend `route` helper.
        |
        | Example: 'exclude' => ['ignition.healthCheck']
        |
        */
        'exclude' => [
            // 'ignition.healthCheck',
        ],
        
        /*
        |--------------------------------------------------------------------------
        | Middleware Configuration for Luminix Routes
        |--------------------------------------------------------------------------
        |
        | Determines the middleware applied to Luminix routes, crucial for implementing
        | secure, authenticated, and authorized access to Luminix functionalities.
        |
        */
        'middleware' => [

            /*
            |--------------------------------------------------------------------------
            | Initialization Route Middleware
            |--------------------------------------------------------------------------
            |
            | 'init' middleware is assigned to the Luminix initialization route, ensuring
            | secure and efficient bootstrapping of the package. Applicable if 'boot.method'
            | is 'api'.
            |
            */
            'init' => ['api'],

            /*
            |--------------------------------------------------------------------------
            | API Route Middleware
            |--------------------------------------------------------------------------
            |
            | 'api' middleware applies to all Luminix API routes, reinforcing secure and
            | efficient interactions with Luminix's API endpoints.
            |
            */
            'api' => ['api', 'auth', 'can:access-luminix'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Publicly Accessible Resources
    |--------------------------------------------------------------------------
    |
    | Identifies models and routes that should be publicly accessible, bypassing
    | authentication. Useful for creating public areas in applications utilizing Luminix.
    |
    */
    'public' => [

        /*
        |--------------------------------------------------------------------------
        | Publicly Accessible Luminix Models
        |--------------------------------------------------------------------------
        |
        | Identifies models that should be publicly accessible, bypassing authentication.
        | Useful for creating public areas in applications utilizing Luminix.
        |
        | Example: 'models' => ['user']
        |
        */
        'models' => [
            // 'user',
        ],

        /*
        |--------------------------------------------------------------------------
        | Publicly Accessible Luminix Routes
        |--------------------------------------------------------------------------
        |
        | Identifies routes that should be publicly accessible, bypassing authentication.
        | Useful for creating public areas in applications utilizing Luminix.
        |
        | Example: 'public' => ['home', 'about']
        |
        */
        'routes' => [
            'login',
            'register',
            'password.request',
            'password.email',
            'password.reset',
            'password.update',
        ],
    ],
];
