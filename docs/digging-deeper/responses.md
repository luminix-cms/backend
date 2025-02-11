# Responses

In some scenarios, you may want to customize the response format of your API endpoints. In many cases, this could be achieved by [leveraging model behaviors](../basics/customize-endpoints.md#add-behaviors-to-the-model-itself), such as hiding attributes or [eager leager-loading.mdr/eager-loading.md). However, in some cases, you may need more granular control over the response format.

Luminix allows you to register a [Laravel Resource](https://laravel.com/docs/11.x/eloquent-resources) class to transform the data before sending it back to the client.

## Registering a Resource

To customize the response format, create a new Resource class using the `artisan` command:

```bash
php artisan make:resource UserResource
```

Follow [Laravel documentation](https://laravel.com/docs/11.x/eloquent-resources) to define the structure of your Resource class. You can customize the data, include additional metadata, and format the response as needed.

To apply the Resource to your API endpoints, add the `Luminix\Backend\Resources\WithResource` attribute to your model, specifying the Resource class as the argument:

```php
use Illuminate\Database\Eloquent\Model;
use Luminix\Backend\Model\LuminixModel;
use Luminix\Backend\Resources\WithResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserCollection;

#[WithResource(single: UserResource::class, collection: UserCollection::class)]
class User extends Model
{
    use LuminixModel;
}
```

In this example, `UserResource` is used for single records, and `UserCollection` is used for paginated responses. 

