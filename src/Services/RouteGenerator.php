<?php

namespace Luminix\Backend\Services;

use Arandu\Reducible\Reducible;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Luminix\Backend\Contracts\LuminixModelInterface;
use Luminix\Backend\Facades\Finder;

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

        if (Finder::classUses($Model, SoftDeletes::class)) {
            $defaultRoutes['restoreMany'] = [
                'path' => $prefix . '/restore',
                'method' => 'post',
            ];
        }

        if ($primaryKey) {
            $defaultRoutes += [
                'show' => $prefix . '/{' . $primaryKey . '}',
                'update' => [
                    'path' => $prefix . '/{' . $primaryKey . '}',
                    'method' => 'post',
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
        

        // Relation Routes
        foreach ($instance->getSyncs() as $relation) {
            $defaultRoutes[$relation . ':sync'] = [
                'path' => $prefix . '/{' . $primaryKey . '}/' . $relation . '/sync',
                'method' => 'post',
            ];

            $defaultRoutes[$relation . ':attach'] = [
                'path' => $prefix . '/{' . $primaryKey . '}/' . $relation . '/{itemId}',
                'method' => 'post',
            ];

            $defaultRoutes[$relation . ':detach'] = [
                'path' => $prefix . '/{' . $primaryKey . '}/' . $relation . '/{itemId}',
                'method' => 'delete',
            ];
        }

        $defaultRoutes = static::modelRoutes($defaultRoutes, $prefix);

        $defaultRoutes = static::{'model' . class_basename($Model) . 'Routes'}($defaultRoutes, $prefix);

        return $defaultRoutes;
    }
}
