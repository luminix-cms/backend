<?php

namespace Luminix\Backend\Services;

use Illuminate\Contracts\Container\BindingResolutionException;
use Doctrine\DBAL\Exception;
use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Luminix\Backend\Macros;
use Luminix\Backend\Support\Traits;

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
            /** @var mixed */
            $container = Container::getInstance();

            $models = collect(File::allFiles(app_path('Models')))
                ->map(function ($item) use ($container) {
                    $path = 'Models\\' . $item->getRelativePathName();

                    return sprintf(
                        '\%s%s',
                        $container->getNamespace(),
                        strtr(substr($path, 0, strrpos($path, '.')), DIRECTORY_SEPARATOR, '\\')
                    );
                })
                ->filter(function ($class) {
                    $valid = false;

                    if (class_exists($class)) {
                        $reflection = new \ReflectionClass($class);

                        $valid = $reflection->isSubclassOf(Model::class)
                            && !$reflection->isAbstract()
                            && Traits::classUses($class, \Luminix\Backend\Model\LuminixModel::class);
                    }

                    return $valid;
                });

            $this->luminixModels = $models->values();
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

                if (!$this->app->runningInConsole() && !Auth::check() && !in_array($snakeName, Config::get('luminix.public.models', []))) {
                    continue;
                }
                
                /** @var Model */
                $instance = new $model;

                $models[$snakeName] = [
                    'fillable' => $instance->getFillable(),
                    'casts' => $instance->getCasts(),
                    'primaryKey' => $instance->getKeyName(),
                    'labeledBy' => $instance->getLabel(),
                    'timestamps' => $instance->usesTimestamps(),
                    'softDeletes' => Traits::classUses($model, \Illuminate\Database\Eloquent\SoftDeletes::class),
                    'importable' => Traits::classUses($model, \Luminix\Backend\Model\Importable::class),
                    'exportable' => Traits::classUses($model, \Luminix\Backend\Model\Exportable::class),
                    'relations' => $instance->getRelationships(),
                    // 'attributes' => $modelInfo->attributes->toArray(),
                ];

                if (Macros::hasMacro('modelManifest')) {
                    $models[$snakeName] = Macros::modelManifest($models[$snakeName], $model);
                }
                if (Macros::hasMacro('model' . class_basename($model) . 'Manifest')) {
                    $models[$snakeName] = Macros::{'model' . class_basename($model) . 'Manifest'}($models[$snakeName], $model);
                }
            }
            if (empty($models)) {
                $this->models = new \stdClass();
            } else {
                $this->models = $models;
            }
        }

        return $this->models;
    }

    public function routes()
    {
        if (!isset($this->routes)) {
            $routes = [];

            $routeList = Route::getRoutes()->getRoutesByName();

            foreach ($routeList as $name => $route) {
                if (in_array($name, Config::get('luminix.routing.exclude', []) + ['luminix.init'])) {
                    continue;
                }

                if (!$this->app->runningInConsole() && !Auth::check() && !in_array($name, Config::get('luminix.public.routes', []))) {
                    continue;
                }

                Arr::set($routes, $name, [
                    $route->uri(),
                    ...collect($route->methods())
                        ->filter(fn ($method) => !in_array($method, ['HEAD', 'OPTIONS']))
                        ->map(fn ($method) => Str::lower($method))
                        ->values()
                ]);
            }

            $this->routes = $routes;
        }

        return $this->routes;
    }

    public function makeBoot()
    {
        $response = [
            'data' => [
                'user' => auth()->user(),
            ],
        ];

        if (Macros::hasMacro('onInit')) {
            $response['data'] += Macros::onInit();
        }

        if (Config::get('luminix.boot.includes_manifest_data', true)) {
            $response += [
                'models' => $this->models(),
                'routes' => $this->routes(),
            ];
        }

        return $response;
    }

}
