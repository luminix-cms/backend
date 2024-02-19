<?php

namespace Luminix\Backend;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Luminix\Backend\Services\Js;
use Luminix\Backend\Services\Manifest;
use Luminix\Backend\Services\ModelFinder;

class BackendServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        
        $this->app->singleton(ModelFinder::class, function () {
            return new ModelFinder($this->app);
        });
    }
    
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/backend.php', 'luminix.backend');

        $this->publishes([
            __DIR__ . '/../config/backend.php' => config_path('luminix/backend.php'),
        ], 'luminix-config');

    }
}