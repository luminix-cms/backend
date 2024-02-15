<?php

namespace Luminix\Backend\Services;

use Illuminate\Contracts\Container\BindingResolutionException;
use Doctrine\DBAL\Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Spatie\ModelInfo\ModelFinder;
use Illuminate\Support\Str;
use Luminix\Backend\Macros;
use Luminix\Backend\Support\Traits;
use Spatie\ModelInfo\ModelInfo;

class Manifest
{
    protected $luminixModels;
    protected $models;
    protected $routes;

    public function __construct(
        protected Application $app
    )
    {
    }

    /**
     * Get a list of models enabled for Luminix
     *
     * @return \Illuminate\Support\Collection<class-string<LuminixModel>> 
     */
    public function luminixModels()
    {
        if (!isset($this->luminixModels)) {
            $this->luminixModels = ModelFinder::all()
                ->filter(fn ($model) => Traits::classUses($model, \Luminix\Backend\Model\LuminixModel::class))
                ->values();
        }
        return $this->luminixModels;
    }

    /**
     * Get the model manifest
     * 
     * @return array 
     * @throws BindingResolutionException 
     * @throws Exception 
     */
    public function models()
    {
        if (!isset($this->models)) {
            $modelList = $this->luminixModels();
            $models = [];

            foreach ($modelList as $model) {
                $snakeName = Str::snake(class_basename($model));
                $modelInfo = ModelInfo::forModel($model);

                /** @var Model */
                $instance = new $model;

                $models[$snakeName] = [
                    'fillable' => $instance->getFillable(),
                    'casts' => $instance->getCasts(),
                    'primaryKey' => $instance->getKeyName(),
                    'timestamps' => $instance->usesTimestamps(),
                    'softDeletes' => Traits::classUses($model, \Illuminate\Database\Eloquent\SoftDeletes::class),
                    'importable' => Traits::classUses($model, \Luminix\Backend\Model\Importable::class),
                    'exportable' => Traits::classUses($model, \Luminix\Backend\Model\Exportable::class),
                    'relations' => $modelInfo->relations->toArray(),
                    // 'attributes' => $modelInfo->attributes->toArray(),
                ];

                if (Macros::hasMacro('modelManifest')) {
                    $models[$snakeName] = Macros::modelManifest($models[$snakeName], $model);
                }
                if (Macros::hasMacro('model' . class_basename($model) . 'Manifest')) {
                    $models[$snakeName] = Macros::{'model' . class_basename($model) . 'Manifest'}($models[$snakeName], $model);
                }
            }
            $this->models = $models;
        }

        return $this->models;
    }

    public function routes()
    {
        if (!isset($this->routes)) {
            $routes = [];

            $routeList = Route::getRoutes()->getRoutesByName();

            foreach ($routeList as $name => $route) {
                if (in_array($name, Config::get('luminix.routing.exclude', []))) {
                    continue;
                }

                // if (!$this->app->runningInConsole() && !Auth::check() && !in_array($name, Config::get('luminix.routing.public', []))) {
                //     continue;
                // }

                Arr::set($routes, $name, $route->uri());
            }

            $this->routes = $routes;
        }

        return $this->routes;
    }

}
