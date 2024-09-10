<?php

namespace Luminix\Backend\Facades;

use Illuminate\Support\Facades\Facade;
use Luminix\Backend\Services\ModelFinder;

/**
 * 
 * Find Luminix models registered in the application.
 * 
 * @method static bool classUses($class, $trait, $recursive = true)
 * @method static \Illuminate\Support\Collection<string, string> all()
 * @method static string toAlias(string $class)
 * @method static string toClass(string $alias)
 * @method static bool isLuminixModel(string|object $class)
 * 
 */
class Finder extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ModelFinder::class;
    }
}

