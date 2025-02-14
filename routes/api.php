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
            $middleware = null;

            if (is_array($path)) {
                $method = $path['method'] ?? 'get';
                $middleware = $path['middleware'] ?? null;
                $path = $path['path'];
            }

            $controller = $class::getController();

            $endpoint = str_contains($action, ':') ? explode(':', $action)[1] : $action;

            if ($middleware) {
                Route::middleware($middleware)->{$method}($path, $controller . '@' . $endpoint)->name('luminix.' . $alias . '.' . $action);
            } else {
                Route::{$method}($path, $controller . '@' . $endpoint)->name('luminix.' . $alias . '.' . $action);
            }
            
        }
    });
});
