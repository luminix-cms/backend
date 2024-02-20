<?php

namespace Luminix\Backend\Model;

use Illuminate\Support\Str;
use Luminix\Backend\Services\ModelFinder;

trait HasRestApi
{
    /**
     * Merge the default routes with the given routes array.
     * 
     * @param array<string,string|array> $routes 
     * @return array<string,string|array> 
     */
    static function mergeDefaultRoutes(array $routes)
    {
        $prefix = Str::plural(Str::snake(class_basename(static::class)));

        $primaryKey = (new static)->getKeyName();

        // Default Laravel Resource Routes
        $defaultRoutes = [
            'index' => $prefix,
            'store' => [
                'url' => $prefix,
                'method' => 'post',
            ],
        ];

        if ($primaryKey) {
            $defaultRoutes += [
                'show' => $prefix . '/{' . $primaryKey . '}',
                'update' => [
                    'url' => $prefix . '/{' . $primaryKey . '}',
                    'method' => 'put',
                ],
                'destroy' => [
                    'url' => $prefix . '/{' . $primaryKey . '}',
                    'method' => 'delete',
                ],
            ];
        }

        // Additional Rotues
        $defaultRoutes['destroyMany'] = [
            'url' => $prefix,
            'method' => 'delete',
        ];
        $defaultRoutes['restoreMany'] = [
            'url' => $prefix,
            'method' => 'put',
        ];

        if (app(ModelFinder::class)->classUses(static::class, Importable::class)) {
            $defaultRoutes['import'] = [
                'url' => $prefix . '/import',
                'method' => 'post',
            ];
        }

        if (app(ModelFinder::class)->classUses(static::class, Exportable::class)) {
            $defaultRoutes['export'] = [
                'url' => $prefix . '/export',
                'method' => 'get',
            ];
        }

        return $defaultRoutes + $routes;
    }

    /**
     * Get the routes for the model.
     * 
     * @return string[]
     */
    static function getLuminixRoutes(): array
    {
        return static::mergeDefaultRoutes([]);
    }


}