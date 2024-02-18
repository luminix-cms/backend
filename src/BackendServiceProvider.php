<?php

namespace Luminix\Backend;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Luminix\Backend\Services\Js;
use Luminix\Backend\Services\Manifest;

class BackendServiceProvider extends ServiceProvider
{
    public function boot()
    {

        
        $this->app->singleton(Manifest::class, function () {
            return new Manifest($this->app);
        });
        
        $this->app->singleton(Js::class, function () {
            return new Js();
        });
        
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        
        $this->loadViewsFrom(__DIR__ . '/../views', 'luminix');
        
        Blade::directive('luminixEmbed', function () {
            return "<?php echo view('luminix::embed')->render(); ?>";
        });

        View::composer('luminix::embed', function ($_) {
            if (config('luminix.boot.method', 'api') === 'embed') {
                app(Js::class)->set('boot', app(Manifest::class)->makeBoot());
            }
        });
    }
    
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/luminix.php', 'luminix');

        $this->publishes([
            __DIR__ . '/../config/luminix.php' => config_path('luminix.php'),
        ], 'luminix-config');

    }
}