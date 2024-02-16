<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Luminix\Backend\Services\Manifest;

if (Config::get('luminix.boot.method', 'api') === 'api') {
    Route::middleware(Config::get('luminix.routing.middleware.init', ['api']))
        ->get('api/' . Config::get('luminix.routing.prefix', 'luminix') . '/init', 'Luminix\Backend\Controllers\InitController@init')
        ->name('luminix.init');
}

Route::group([
    'middleware' => Config::get('luminix.routing.middleware.api', ['api', 'auth', 'can:access-luminix']),
    'prefix' => 'api/' . Config::get('luminix.routing.prefix', 'luminix'),
], function () {

    /** @var Manifest */
    $manifest = app(Manifest::class);


    foreach ($manifest->luminixModels() as $model) {
        $routes = $model::getLuminixRoutes();

        foreach ($routes as $action => $url) {
            $method = 'get';

            if (is_array($url)) {
                $method = $url['method'];
                $url = $url['url'];
            }

            $overrides = Config::get('luminix.routing.controller_overrides', []);

            $controller = $overrides[$model] ?? Config::get('luminix.routing.controller', 'Luminix\Backend\Controllers\ResourceController');

            $modelName = Str::snake(class_basename($model));

            Route::$method($url, $controller . '@' . $action)->name('luminix.' . $modelName . '.' . $action);
        }

    }

});

