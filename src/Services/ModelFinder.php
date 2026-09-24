<?php

namespace Luminix\Backend\Services;

use Arandu\Reducible\Reducible;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
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

            return array_merge($prevList, $models);
        });

    }

    function all()
    {
        if (!isset($this->classes)) {

            $directory = app_path(config('luminix.backend.models.directory', 'Models'));
            $namespace = App::getNamespace() . str_replace(
                '/',
                '\\',
                config('luminix.backend.models.directory', 'Models')
            );

            $models = [];
            if (is_dir($directory)) {
                $files = File::allFiles($directory);
                foreach ($files as $file) {
                    $class = $namespace . '\\' . str_replace(
                        ['/', '.php'],
                        ['\\', ''],
                        $file->getRelativePathname()
                    );
                    if (class_exists($class)) {
                        $models[] = $class;
                    }
                }
            }

            $models = array_merge($models, config('luminix.backend.models.include', []));

            $models = $this->models($models);

            $this->classes = collect($models)
                ->filter(function($model) {
                    return class_exists($model) && $this->isLuminixModel($model);
                })
                ->mapWithKeys(function($model) {
                    return [$model::getAlias() => $model];
                });
        }
        return $this->classes;
    }

    /**
     * The alias encoded in a Luminix route name, or null when the name does not
     * belong to this package.
     *
     * Route names follow `luminix.{alias}.{action}`, and that shape is load
     * bearing: this package splits it in `ResourceController`, and consuming
     * applications split it again whenever they need to know which model an
     * automatic endpoint belongs to — to gate it behind a feature flag, for
     * instance.
     *
     * Every one of those consumers is a copy of a convention we never exposed.
     * The day the shape changes, they stop matching in silence: no exception,
     * no failing test, just endpoints answering when they should not. Reading
     * the alias from here keeps the convention in one place.
     */
    function aliasFromRouteName(?string $name): ?string
    {
        return $this->splitRouteName($name)[0] ?? null;
    }

    /**
     * The action encoded in a Luminix route name, or null when the name does
     * not belong to this package. Relation actions keep their `{relation}:{action}`
     * shape, as the routes declare them.
     */
    function actionFromRouteName(?string $name): ?string
    {
        return $this->splitRouteName($name)[1] ?? null;
    }

    /**
     * @return array{0: string, 1: string}|array{}
     */
    private function splitRouteName(?string $name): array
    {
        if (!is_string($name)) {
            return [];
        }

        $parts = explode('.', $name);

        if (count($parts) !== 3 || $parts[0] !== 'luminix') {
            return [];
        }

        return [$parts[1], $parts[2]];
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
