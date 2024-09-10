<?php

namespace Luminix\Backend\Model;

use Luminix\Backend\Services\RouteGenerator;

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
        return RouteGenerator::make(static::class) + $routes;
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