<?php

/*
|--------------------------------------------------------------------------
| Luminix Package Configuration
|--------------------------------------------------------------------------
|
| Here you can configure the settings for the Luminix package. This file is
| structured to provide intuitive and comprehensive control over various features
| of Luminix, including initialization, routing, and model interactions. 
| Each option is documented to facilitate easy customization tailored to the
| unique requirements of your application.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Initialization Settings for Luminix
    |--------------------------------------------------------------------------
    |
    | The 'boot' section contains settings that determine how Luminix initializes
    | in your application. This includes the boot method, manifest data inclusion,
    | and other essential startup configurations. These settings are vital for
    | ensuring that Luminix integrates smoothly with your application's architecture.
    |
    */
    'boot' => [

        /*
        |--------------------------------------------------------------------------
        | Boot Method Configuration
        |--------------------------------------------------------------------------
        |
        | 'method' determines the initialization strategy for Luminix. Available options:
        |
        | 'api': Luminix initializes through an API call. Ideal for decoupled architectures
        |        or single-page applications (SPAs).
        | 'embed': Embeds boot data directly in HTML. Best suited for server-rendered apps.
        |
        | You can dynamically override this value using the LUMINIX_BOOT_METHOD env variable.
        |
        */
        'method' => env('LUMINIX_BOOT_METHOD', 'api'),

        /*
        |--------------------------------------------------------------------------
        | Manifest Data Inclusion
        |--------------------------------------------------------------------------
        |
        | Controls whether manifest data, including models and routes, is included during boot.
        | A value of true incorporates both dynamic and static manifest data. If set to false,
        | manifest data must be generated manually using the artisan command.
        |
        | Useful for fine-tuning the boot payload size and managing data exposure.
        |
        */
        'includes_manifest_data' => env('LUMINIX_BOOT_INCLUDES_MANIFEST_DATA', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Routing Configuration for Luminix
    |--------------------------------------------------------------------------
    |
    | The 'routing' section manages how Luminix handles routing. It covers settings
    | for model controller overrides, route prefixes, middleware application, and
    | route exclusions. These settings are crucial for routing management and security.
    |
    */
    'routing' => [

        /*
        |--------------------------------------------------------------------------
        | Route Prefix for Luminix
        |--------------------------------------------------------------------------
        |
        | 'prefix' sets a URL prefix for all Luminix routes, aiding in avoiding route
        | conflicts and ensuring compatibility with your app's URL scheme.
        |
        | For instance, setting 'prefix' => 'luminix' would prefix all Luminix routes
        | with 'yourapp.com/luminix/...'.
        |
        */
        'prefix' => 'luminix',

        /*
        |--------------------------------------------------------------------------
        | Middleware for Luminix Routes
        |--------------------------------------------------------------------------
        |
        | Specifies middleware to be applied to Luminix routes. Essential for implementing
        | authentication, authorization, or custom logic for secure access to Luminix features.
        |
        */
        'middleware' => ['api', 'auth', 'can:access-luminix'],

        /*
        |--------------------------------------------------------------------------
        | Custom Model Controller Overrides
        |--------------------------------------------------------------------------
        |
        | Allows for the specification of custom controllers for certain models, enabling
        | tailored logic or routing distinct from Luminix's default behavior.
        |
        | Use the format: 'ModelClassName' => 'ControllerClassName'.
        | For example: 'App\Models\User' => 'App\Http\Controllers\UserController'.
        |
        */
        'controller' => [
            // 'App\Models\User' => 'App\Http\Controllers\UserController',
        ],

        /*
        |--------------------------------------------------------------------------
        | Route Exclusions in Luminix
        |--------------------------------------------------------------------------
        |
        | Lists route names to be excluded from the Luminix route manifest. Routes listed
        | here will not be accessible via the `route` helper from `@luminix/core` on the frontend.
        |
        | Example: 'exclude' => ['ignition.healthCheck']
        |
        */
        'exclude' => [
            // 'ignition.healthCheck',
        ],

        /*
        |--------------------------------------------------------------------------
        | Public Routes in Luminix
        |--------------------------------------------------------------------------
        |
        | Identifies routes that should be publicly accessible, i.e., without authentication.
        | This facilitates the creation of public areas in your application using `@luminix/core`.
        |
        | Example: 'public' => ['home', 'about']
        |
        */
        'public' => [
            'login',
            'register',
            'password.request',
            'password.email',
            'password.reset',
            'password.update',
        ],
    ],
];
