<?php

namespace Luminix\Backend\Model;

use Illuminate\Support\Str;
use Luminix\Backend\Macros;
use Luminix\Backend\Support\Traits;

trait GeneratesRoutes
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

        // Default Laravel Resource Routes
        $defaultRoutes = [
            'index' => $prefix,
            'store' => [
                'url' => $prefix,
                'method' => 'post',
            ],
            'show' => $prefix . '/{id}',
            'update' => [
                'url' => $prefix . '/{id}',
                'method' => 'put',
            ],
            'destroy' => [
                'url' => $prefix . '/{id}',
                'method' => 'delete',
            ],
        ];

        // Additional Rotues
        $defaultRoutes['destroyMany'] = [
            'url' => $prefix,
            'method' => 'delete',
        ];
        $defaultRoutes['restoreMany'] = [
            'url' => $prefix,
            'method' => 'put',
        ];

        if (Traits::classUses(static::class, Importable::class)) {
            $defaultRoutes['import'] = [
                'url' => $prefix . '/import',
                'method' => 'post',
            ];
        }

        if (Traits::classUses(static::class, Exportable::class)) {
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
    static function getLuminixRoutes()
    {
        return static::mergeDefaultRoutes([]);
    }


}