<?php

namespace Luminix\Backend\Services;

use Arandu\Reducible\Reducible;
use HaydenPierce\ClassFinder\ClassFinder;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Luminix\Backend\Model\LuminixModel;
use Luminix\Backend\Contracts\LuminixModelInterface;

class ModelFinder {

    use Reducible;

    /** @var Collection<string,string> */
    private $classes;

    function classUses($class, $trait, $recursive = true)
    {
        return in_array(
            $trait,
            $recursive
                ? class_uses_recursive($class)
                : class_uses($class)
        );
    }

    function isLuminixModel(string|object $class): bool
    {
        $reflection = new \ReflectionClass($class);

        return $reflection->isSubclassOf(Model::class)
            && !$reflection->isAbstract()
            && (
                $this->classUses($class, LuminixModel::class)
                || $reflection->implementsInterface(LuminixModelInterface::class)
            ); 
    }

    /**
     * Add models to the list of models.
     * 
     * @param string|array $models - The classnames of the models to add.
     * @return void 
     */
    static function addModels($models)
    {
        static::reducer('models', function ($prevList) use ($models) {
            if (is_string($models)) {
                $models = [$models];
            }

            return $prevList + $models;
        });

    }

    function all()
    {
        if (!isset($this->classes)) {
            ClassFinder::disablePSR4Vendors();

            $namespace = config('luminix.backend.models.namespace', 'App\Models');

            $models = $namespace
                ? ClassFinder::getClassesInNamespace($namespace)
                : [];

            $models += config('luminix.backend.models.include', []);

            $models = $this->models($models);

            $this->classes = collect($models)
                ->filter(function($model) {
                    if (!class_exists($model)) {
                        return false;
                    }

                    return $this->isLuminixModel($model);
                })
                ->mapWithKeys(function($model) {
                    return [$model::getAlias() => $model];
                });
        }
        return $this->classes;
    }

    function toAlias(string $model): string
    {
        return $this->all()->search($model);
    }

    function toClass(string $alias): string
    {
        return $this->all()[$alias];
    }

}
