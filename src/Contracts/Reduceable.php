<?php

namespace Luminix\Backend\Contracts;

trait Reduceable
{

    static $reducers = [];

    static function reducer(string $key, \Closure $reducer, $priority = 10)
    {
        if (!isset(static::$reducers[$key])) {
            static::$reducers[$key] = [];
        }
        static::$reducers[$key] = collect(static::$reducers[$key] + [['reducer' => $reducer, 'priority' => $priority]])
            ->sortBy('priority')
            ->values()
            ->toArray();

        return function () use ($key, $reducer) {
            static::removeReducer($key, $reducer);
        };
    }

    static function removeReducer(string $key, \Closure $reducer)
    {
        if (isset(static::$reducers[$key])) {
            static::$reducers[$key] = collect(static::$reducers[$key])
                ->filter(fn($item) => $item['reducer'] !== $reducer)
                ->values()
                ->toArray();
        }
    }

    static function getReducer(string $key)
    {
        return static::$reducers[$key] ?? [];
    }

    static function hasReducer(string $key)
    {
        return isset(static::$reducers[$key]);
    }

    static function clearReducer(string $key)
    {
        static::$reducers[$key] = [];
    }

    static function flushReducers()
    {
        static::$reducers = [];
    }

    static function __callStatic($name, $arguments)
    {
        if (empty($arguments)) {
            throw new \InvalidArgumentException('No value provided for reducer.');
        }
    
        $value = array_shift($arguments);

        return collect(static::getReducer($name))
            ->reduce(function ($carry, $item) use ($arguments) {
                $reducer = $item['reducer'];

                if (!($reducer instanceof \Closure)) {
                    return $carry;
                }

                $reducer = $reducer->bindTo(null, static::class);

                $expected = (new \ReflectionFunction($reducer))->getNumberOfParameters();

                $parameters = $arguments;

                if ($expected > count($parameters)) {
                    $parameters = array_pad($parameters, $expected, null);
                }

                $parameters = array_slice($parameters, 0, $expected);

                return $reducer($carry, ...$parameters);
            }, $value);
    }

    function __call($method, $parameters)
    {
        if (empty($parameters)) {
            throw new \InvalidArgumentException('No value provided for reducer.');
        }

        $value = array_shift($parameters);

        return collect(static::getReducer($method))
            ->reduce(function ($carry, $item) use ($parameters) {
                $reducer = $item['reducer'];

                if (!($reducer instanceof \Closure)) {
                    return $carry;
                }

                $reducer = $reducer->bindTo($this, static::class);

                $expected = (new \ReflectionFunction($reducer))->getNumberOfParameters();
                $arguments = $parameters;

                $arguments = array_slice($arguments, 0, $expected);

                return $reducer($carry, ...$arguments);
            }, $value);
    }

}