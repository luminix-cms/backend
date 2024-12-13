# Adding Models to Luminix

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

4. **Primary Key**: Models must have a primary key defined.

## Next Steps

 - [Automatic Endpoint Generation](2a-api-endpoints.md)
 - [Back to Documentation Index](0-index.md)
