# Luminix Backend

Luminix Backend is a powerful Laravel package that automatically generates RESTful API endpoints for your models with built-in security, filtering, and permission management.

## Features

- 🚀 Automatic API Endpoint Generation
- 🔒 Robust Security Controls
- 🔍 Advanced API Filtering
- 🛡️ Laravel Gate Integration
- 🔧 Highly Configurable

## Installation

Install the package via Composer:

```bash
composer require luminix/backend
```

## Quick Start

1. Add the `Luminix\Backend\Model\LuminixModel` trait to your models:

```php
use Illuminate\Database\Eloquent\Model;
use Luminix\Backend\Model\LuminixModel;

class User extends Model
{
    use LuminixModel;
    
    // Your model code
}
```

2. Publish the configuration file:

```bash
php artisan vendor:publish --tag=luminix-config
```

3. Configure the package in `config/luminix/backend.php`

## Documentation

Proceed to the [documentation](docs/0-index.md) for detailed instructions on using Luminix Backend.

## Contributing

Contributions are welcome! Please submit pull requests or open issues on our GitHub repository.

## License

[MIT](https://opensource.org/licenses/MIT).

## Support

For support, please open an issue on the GitHub repository.
