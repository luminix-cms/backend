<?php

namespace Luminix\Backend;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Luminix\Backend\Services\ModelFinder;

class BackendServiceProvider extends ServiceProvider
{

    protected static $preventEnforcingMorphMap = false;

    public static function preventEnforcingMorphMap()
    {
        static::$preventEnforcingMorphMap = true;
    }

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'luminix-backend');

        $this->publishes([
            __DIR__ . '/../lang' => $this->app->langPath('vendor/luminix-backend'),
        ], 'luminix-lang');

        // Installs the consumer skill into the app so it triggers without /luminix. The whole
        // tree is copied, so refreshing it after a package upgrade takes `--force`. Shared tag
        // across every luminix/* package -> one `vendor:publish --tag=luminix-skill` covers all
        // of them.
        $this->publishes([
            __DIR__ . '/../skill' => base_path('.claude/skills/luminix-backend'),
        ], 'luminix-skill');
        
        $finder = new ModelFinder();
        $this->app->instance(ModelFinder::class, $finder);

        $this->commands([
            Commands\MakeValidatorCommand::class,
        ]);

        $this->extendValidator();

        if (!static::$preventEnforcingMorphMap) {
            Relation::enforceMorphMap(
                $finder->all()->toArray()
            );
        }
        
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/backend.php', 'luminix.backend');

        $this->publishes([
            __DIR__ . '/../config/backend.php' => config_path('luminix/backend.php'),
        ], 'luminix-config');

    }


    private function extendValidator() {
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

            if (is_int($value) || is_string($value)) {
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
}
