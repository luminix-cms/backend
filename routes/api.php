<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Luminix\Backend\Services\Manifest;

Route::group([
    'middleware' => Config::get('luminix.routing.middleware', ['api', 'auth', 'can:access-luminix']),
    'prefix' => 'api/' . Config::get('luminix.routing.prefix', 'luminix'),
], function () {

    /** @var Manifest */
    $manifest = app(Manifest::class);

    if (Config::get('luminix.boot.method', 'api') === 'api') {
        Route::get('init', 'Luminix\Backend\Controllers\InitController@init')->name('luminix.init');
    }

    foreach ($manifest->luminixModels() as $model) {
        $routes = $model::getLuminixRoutes();

        foreach ($routes as $page => $url) {
            $method = 'get';

            if (is_array($url)) {
                $method = $url['method'];
                $url = $url['url'];
            }

            $overrides = Config::get('luminix.routing.controller', []);

            $controller = $overrides[$model] ?? 'Luminix\Backend\Controllers\ResourceController';

            Route::$method($url, $controller . '@' . $page)->name('luminix.' . Str::snake(class_basename($model)) . '.' . $page);
        }

    }

});

