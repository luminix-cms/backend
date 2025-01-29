<?php

namespace Luminix\Backend\Model;

use Luminix\Backend\Controllers\ResourceController;
use Luminix\Backend\Controllers\WithController;
use Luminix\Backend\Services\RouteGenerator;

trait HasRestApi
{
    /**
     * Merge the default routes with the given routes array.
     * 
     * @param array<string,string|array> $routes 
     * @return array<string,string|array> 
     */
    static function getDefaultRoutes()
    {
        return RouteGenerator::make(static::class);
    }

    /**
     * Get the routes for the model.
     * 
     * @return string[]
     */
    static function getLuminixRoutes(): array
    {
        return static::getDefaultRoutes();
    }

    static function getController(): string
    {
        $attributes = (new \ReflectionClass(static::class))->getAttributes(WithController::class);

        if (count($attributes) > 0) {
            return $attributes[0]->newInstance()->getController();
        }

        return ResourceController::class;
    }


}