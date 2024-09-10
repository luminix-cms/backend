<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Luminix\Backend\Facades\Finder;

Route::group([
    'middleware' => Config::get('luminix.backend.security.middleware', ['api', 'auth']),
    'prefix' => Config::get('luminix.backend.api.prefix', 'luminix-api'),
], function () {


    Finder::all()->each(function ($class, $alias) {
        $routes = $class::getLuminixRoutes();

        foreach ($routes as $action => $path) {
            $method = 'get';

            if (is_array($path)) {
                $method = $path['method'];
                $path = $path['path'];
            }

            $overrides = Config::get('luminix.backend.api.controller_overrides', []);

            $controller = $overrides[$class] ?? Config::get('luminix.backend.api.controller', 'Luminix\Backend\Controllers\ResourceController');

            $endpoint = str_contains($action, ':') ? explode(':', $action)[1] : $action;

            Route::$method($path, $controller . '@' . $endpoint)->name('luminix.' . $alias . '.' . $action);
        }
    });
});
