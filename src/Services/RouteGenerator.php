<?php

namespace Luminix\Backend\Services;

use Arandu\Reducible\Reducible;
use Illuminate\Support\Str;
use Luminix\Backend\Contracts\LuminixModelInterface;

class RouteGenerator
{
    use Reducible;

    static function make(string $Model)
    {
        if (!class_exists($Model)) {
            throw new \InvalidArgumentException("Model $Model does not exist.");
        }

        if (!(new \ReflectionClass($Model))->isSubclassOf('Illuminate\Database\Eloquent\Model')) {
            throw new \InvalidArgumentException("Model $Model is not an Eloquent Model.");
        }

        $prefix = Str::plural(Str::snake(class_basename($Model)));

        $instance = new $Model;
        $primaryKey = $instance->getKeyName();

        // Default Laravel Resource Routes
        $defaultRoutes = [
            'index' => $prefix,
            'store' => [
                'path' => $prefix,
                'method' => 'post',
            ],
        ];

        if ($primaryKey) {
            $defaultRoutes += [
                'show' => $prefix . '/{' . $primaryKey . '}',
                'update' => [
                    'path' => $prefix . '/{' . $primaryKey . '}',
                    'method' => 'put',
                ],
                'destroy' => [
                    'path' => $prefix . '/{' . $primaryKey . '}',
                    'method' => 'delete',
                ],
            ];
        }

        // Additional Rotues
        $defaultRoutes['destroyMany'] = [
            'path' => $prefix,
            'method' => 'delete',
        ];
        $defaultRoutes['restoreMany'] = [
            'path' => $prefix,
            'method' => 'put',
        ];

        // Relation Routes
        if (!empty($instance->getSyncs())) {
            $defaultRoutes['sync'] = [
                'path' => $prefix . '/{id}/{relation}/sync',
                'method' => 'post',
            ];

            $defaultRoutes['attach'] = [
                'path' => $prefix . '/{id}/{relation}/attach',
                'method' => 'post',
            ];

            $defaultRoutes['detach'] = [
                'path' => $prefix . '/{id}/{relation}/detach',
                'method' => 'post',
            ];
        }

        $defaultRoutes = static::modelRoutes($defaultRoutes, $prefix);

        $defaultRoutes = static::{'model' . class_basename($Model) . 'Routes'}($defaultRoutes, $prefix);

        return $defaultRoutes;
    }
}
