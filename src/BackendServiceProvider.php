<?php

namespace Luminix\Backend;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
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


        Validator::extend('luminix_sync', function ($attribute, $value, $parameters, $validator) {
            $class = $parameters[0];
            $relationName = $parameters[1];

            $model = new $class;
            /** @var Relation */
            $relation = $model->{$relationName}();

            $query = $relation->getRelated()
                ->newQuery()
                ->where(function ($query) {
                    $query->allowed(config('luminix.backend.security.permissions.index', 'read'));
                });

            if (is_int($value)) {
                return $query
                    ->where($relation->getRelated()->getKeyName(), $value)
                    ->exists();
            }

            if (!is_array($value)) {
                return false;
            }

            if (!isset($value[$relation->getRelated()->getKeyName()])) {
                return false;
            }

            return $query
                ->where($relation->getRelated()->getKeyName(), $value[$relation->getRelated()->getKeyName()])
                ->exists();

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