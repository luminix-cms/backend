<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Luminix\Backend\Contracts\LuminixModelInterface;
use Luminix\Backend\Services\ModelFinder;

Route::group([
    'middleware' => Config::get('luminix.backend.security.middleware', ['api', 'auth']),
    'prefix' => Config::get('luminix.backend.api.prefix', 'luminix-api'),
], function () {
    /** @var ModelFinder */
    $modelFinder = app(ModelFinder::class);

    $modelFinder->all()->each(function ($class, $alias) {
        $routes = $class::getLuminixRoutes();

        foreach ($routes as $action => $path) {
            $method = 'get';

            if (is_array($path)) {
                $method = $path['method'];
                $path = $path['path'];
            }

            $overrides = Config::get('luminix.backend.api.controller_overrides', []);

            $controller = $overrides[$class] ?? Config::get('luminix.backend.api.controller', 'Luminix\Backend\Controllers\ResourceController');

            Route::$method($path, $controller . '@' . $action)->name('luminix.' . $alias . '.' . $action);
        }
    });
});
