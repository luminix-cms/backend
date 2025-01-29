# Adding REST API

To enable Luminix Backend to generate API endpoints for your models, you need to add the `LuminixModel` trait to each model class you want to expose.

```php
use Illuminate\Database\Eloquent\Model;
use Luminix\Backend\Model\LuminixModel;

class User extends Model
{
    use LuminixModel;
    
    // Your model code
}
```

After adding the trait, Luminix will automatically detect and process the model, generating API endpoints for it.

## Model Requirements

To ensure proper API generation, your models must meet the following requirements:

1. **Model Namespace**: Models must be in the namespace specified in the configuration file. By default, Luminix looks for models in the `App\Models` namespace.

2. **use LuminixModel**: The `LuminixModel` trait must be included in the model class. This trait provides the necessary functionality for API generation.

3. **Fillable Attributes**: Models should define the `$fillable` property to specify which attributes can be mass-assigned. This is important for creating and updating resources via the API.

4. **Primary Key**: Models without primary key are not supported. The [Eloquent primary key](https://laravel.com/docs/11.x/eloquent#primary-keys) will be used to identify resources.


## Model Aliases

Each model will have an alias generated based on the model class name, without the namespace. The alias is used in the route naming and URL generation. By default, the alias is the model class name in snake case. You can customize the alias by defining a `getAlias` static method in your model.

```php
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    public static function getAlias(): string
    {
        return 'my_user';
    }
}
```

The routes will be named using the alias. For example, the index route for the `User` model will be named `luminix.my_user.index`. The alias will then be pluralized and "slugified" to generate the URL. For example, the base URL for the `User` model above will be `/luminix-api/my-users`.

### Model Aliases in Polymorphic Relationships

By default, Luminix Backend will register [Custom Polymorphic Types](https://laravel.com/docs/11.x/eloquent-relationships#custom-polymorphic-types) for all Luminix-enabled models. This means that the model alias will be used as the morph type in polymorphic relationships. If you want to prevent this, call the `preventEnforcingMorphMap` method in the `register` method of your service provider.

```php
// app/Providers/AppServiceProvider.php

use Luminix\Backend\BackendServiceProvider;

function register() {
    BackendServiceProvider::preventEnforcingMorphMap();
}

```

