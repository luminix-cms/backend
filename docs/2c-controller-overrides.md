# Controller Overrides

Luminix provides a number of ways to override the default behavior of the REST API. Here are some of the most common ways to customize the behavior of your API controllers.

## Add behaviors to the model itself

The easiest way to customize the behavior of a model is to add behaviors directly to the model class. For example, make use of the laravel's built-in [Eloquent Model Events](https://laravel.com/docs/11.x/eloquent#events) to add custom logic to your models.

```php
class ToDo extends Model
{
    protected static function booted()
    {
        static::creating(function ($todo) {
            $todo->user_id = auth()->id();
        });
    }
}
```

In the example above, the `creating` event is used to automatically set the `user_id` field of a `ToDo` model to the ID of the currently authenticated user. This ensures that the user who created the `ToDo` is always associated with it.

Other important features to consider are [Hidden Attributes](https://laravel.com/docs/11.x/eloquent-serialization#hiding-attributes-from-json), [Accessors and Mutators](https://laravel.com/docs/11.x/eloquent-mutators) and [Attribute Casting](https://laravel.com/docs/11.x/eloquent-mutators#attribute-casting).

Keep in mind that this approach will be applied globally to the model, so it may not be suitable for all use cases.

## Add model-specific controllers

If you need to customize further the behavior of a specific model REST API, you can create a new controller that extends the default Luminix controller. For example, if you have a `User` model, you can create a `UserController` class that extends the `Luminix\Backend\Controllers\ResourceController` class:

```php
use Luminix\Backend\Controllers\ResourceController;

class UserController extends ResourceController
{
    // Override default behavior here
}
```

Then, you need to assign the new controller to the model in the `config/luminix/backend.php` configuration file. You must have the [configuration published](1a-configuration.md) to do this.

```php
'api' => [
    'controller_overrides' => [
        \App\Models\User::class => \App\Http\Controllers\UserController::class,
    ],
],
```

This will tell Luminix to use the `UserController` class for all API endpoints related to the `User` model.

### Controller "hooks"

The `ResourceController` has two methods that can be overriden to customize the behavior for most applicable cases:

- `beforeSave`: This method is called before saving a model instance. You can use it to modify the model instance before it is saved to the database.

- `afterSave`: This method is called after saving a model instance. You can use it to perform additional actions after the model has been saved.

Here's an example of how you might use these methods in a custom controller:

```php

use Luminix\Backend\Controllers\ResourceController;

class UserController extends ResourceController
{
    protected function beforeSave($model, $data)
    {
        $avatar = $data['avatar'] ?? null;

        if ($avatar) {
            $filename = \Str::random(10) . '.' . $avatar->getClientOriginalExtension();
            \Storage::disk('public')->put('avatars/' . $filename, $avatar);
            $model->avatar = $filename;
        }
    }

}
```

In this example, the `beforeSave` method is used to save an avatar image to the `public` disk before saving the `User` model instance. In this particular case it is preferable that the `avatar` field is not fillable in the model, as it is being set manually. Also, you should not `save` the model instance in the `beforeSave` method, as it will be saved later by the controller.

### Override action methods

You can also override the default action methods in the controller to customize the behavior of specific actions. For example, you can override the `store` method to make your own logic for creating a new resource. Keep in mind that using this approach, you will need to handle all the logic for the action, including validation and error handling.

```php
use Luminix\Backend\Controllers\ResourceController;

class UserController extends ResourceController
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = new User($data);
        $user->save();

        return response()->json($user, 201);
    }
}
```

### Add custom actions

If you need to add custom actions to a model API, you can do so by overriding the `getLuminixRoutes` method in the model. This method should return an array of custom routes that you want to add to the controller.

```php
use Luminix\Backend\Model\LuminixModel;

class User extends Model
{
    use LuminixModel;

    static function getLuminixRoutes(): array
    {
        $routes = static::getDefaultRoutes();

        // Add a custom route to the profile action
        $routes['profile'] = 'profile';

        // To use a HTTP method other than GET, you should set the value as an array
        $routes['addAvatar'] = [
            'method' => 'post',
            'path' => 'add-avatar/{id}',
        ];

        // Disable the default 'destroy' route
        unset($routes['destroy']);

        // Customize the path for the index route
        $routes['index'] = 'custom-users-path';

        return $routes;
    }
}
```

Alternatively, you could add a [reducer](https://github.com/AranduTech/php-reducible) to the `RouteGenerator` service to modify certain model's routes. Reducers should be added in the `boot` method of any service provider loaded by your application. The reducer name should be `'model{$ModelName}Routes'`, where `{$ModelName}` is the name of the model class without the namespace.

```php
use Luminix\Backend\Services\RouteGenerator;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        RouteGenerator::reducer('modelUserRoutes', function ($routes) {
            $routes['profile'] = 'profile';
            $routes['addAvatar'] = [
                'method' => 'post',
                'path' => 'add-avatar/{id}',
            ];
            unset($routes['destroy']);
            $routes['index'] = 'custom-users-path';
            return $routes;
        });
    }
}
```

Then, the logic for the custom action in the controller should be implemented as a new method:

```php
use Luminix\Backend\Controllers\ResourceController;

class UserController extends ResourceController
{
    public function addAvatar(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $avatar = $request->file('avatar');
        $filename = \Str::random(10) . '.' . $avatar->getClientOriginalExtension();
        \Storage::disk('public')->put('avatars/' . $filename, $avatar);
        $user->avatar = $filename;
        $user->save();

        return response()->json($user);
    }
}
```

 > **Note:** Any custom method in the controller should handle all the logic by itself, including validation and error handling.

## Add methods to the base controller

If you need to add common behavior to all controllers, you can add methods to the base controller class. This is useful for adding functionality that should be shared across all controllers, such as custom error handling or logging.

To do this, you need to add a macro to the `ResourceController` class. You can do this in the `boot` method of your `App\Providers\AppServiceProvider` class:

```php
use Luminix\Backend\Controllers\ResourceController;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        ResourceController::macro('logRequest', function ($request) {
            \Log::info('Request received', ['url' => $request->url()]);
        });
    }
}
```
