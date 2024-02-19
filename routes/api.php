<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Luminix\Backend\Services\ModelFinder;

Route::group([
    'middleware' => Config::get('luminix.backend.security.middleware', ['api', 'auth']),
    'prefix' => Config::get('luminix.backend.api.prefix', 'luminix-api'),
], function () {
    /** @var ModelFinder */
    $modelFinder = app(ModelFinder::class);

    $modelFinder->all()->each(function ($class, $alias) {
        $routes = $class::getLuminixRoutes();

        foreach ($routes as $action => $url) {
            $method = 'get';

            if (is_array($url)) {
                $method = $url['method'];
                $url = $url['url'];
            }

            $overrides = Config::get('luminix.backend.api.controller_overrides', []);

            $controller = $overrides[$class] ?? Config::get('luminix.backend.api.controller', 'Luminix\Backend\Controllers\ResourceController');

            Route::$method($url, $controller . '@' . $action)->name('luminix.' . $alias . '.' . $action);
        }
    });
});
