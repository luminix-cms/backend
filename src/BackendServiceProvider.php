<?php

namespace Luminix\Backend;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Luminix\Backend\Services\Manifest;

class BackendServiceProvider extends ServiceProvider
{
    public function boot()
    {

        $this->mergeConfigFrom(__DIR__ . '/../config/luminix.php', 'luminix');

        $this->app->singleton(Manifest::class, function () {
            return new Manifest($this->app);
        });

        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        $this->loadViewsFrom(__DIR__ . '/../views', 'luminix');

        Blade::directive('luminixEmbed', function () {
            return "<?php echo view('luminix::embed')->render(); ?>";
        });
    }
    
    public function register()
    {
        $this->publishes([
            __DIR__ . '/../config/luminix.php' => config_path('luminix.php'),
        ], 'luminix-config');

    }
}