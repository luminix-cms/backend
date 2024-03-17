<?php

namespace Luminix\Backend;

use Illuminate\Support\ServiceProvider;
use Luminix\Backend\Services\ModelFinder;

class BackendServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        
        $this->app->singleton(ModelFinder::class, function () {
            return new ModelFinder();
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