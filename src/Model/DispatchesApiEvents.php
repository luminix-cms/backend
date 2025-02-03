<?php

namespace Luminix\Backend\Model;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait DispatchesApiEvents {

    public static function bootDispatchesApiEvents()
    {
        $observers = Arr::wrap(static::resolveObserveAttributes());

        $events = [
            'luminixCreating',
            'luminixCreated',
            'luminixUpdating',
            'luminixUpdated',
            'luminixSaving',
            'luminixSaved',
            'luminixDeleting',
            'luminixDeleted',
            'luminixRestoring',
            'luminixRestored',
        ];

        foreach ($observers as $observer) {
            foreach ($events as $event) {
                if (method_exists($observer, $event)) {

                    $class = is_object($observer) ? get_class($observer) : $observer;

                    static::registerModelEvent($event, $class . '@' . $event);
                }
            }
        }
    }

    public function initializeDispatchesApiEvents()
    {
        $events = [
            'luminixCreating',
            'luminixCreated',
            'luminixUpdating',
            'luminixUpdated',
            'luminixSaving',
            'luminixSaved',
            'luminixDeleting',
            'luminixDeleted',
            'luminixRestoring',
            'luminixRestored',
        ];

        $this->observables = array_merge($this->observables, $events);

        $this->dispatchesEvents = array_merge([
            'luminixCreating' => \Luminix\Backend\Events\CreatingResource::class,
            'luminixCreated' => \Luminix\Backend\Events\CreatedResource::class,
            'luminixUpdating' => \Luminix\Backend\Events\UpdatingResource::class,
            'luminixUpdated' => \Luminix\Backend\Events\UpdatedResource::class,
            'luminixSaving' => \Luminix\Backend\Events\SavingResource::class,
            'luminixSaved' => \Luminix\Backend\Events\SavedResource::class,
            'luminixDeleting' => \Luminix\Backend\Events\DeletingResource::class,
            'luminixDeleted' => \Luminix\Backend\Events\DeletedResource::class,
            'luminixRestoring' => \Luminix\Backend\Events\RestoringResource::class,
            'luminixRestored' => \Luminix\Backend\Events\RestoredResource::class,
        ], $this->dispatchesEvents);

    }

    public function fireLuminixEvent($event, $halt = false)
    {
        return $this->fireModelEvent('luminix' . Str::studly($event), $halt);
    }

    public static function luminixCreating($callback)
    {
        static::registerModelEvent('luminixCreating', $callback);
    }

    public static function luminixCreated($callback)
    {
        static::registerModelEvent('luminixCreated', $callback);
    }

    public static function luminixUpdating($callback)
    {
        static::registerModelEvent('luminixUpdating', $callback);
    }

    public static function luminixUpdated($callback)
    {
        static::registerModelEvent('luminixUpdated', $callback);
    }

    public static function luminixSaving($callback)
    {
        static::registerModelEvent('luminixSaving', $callback);
    }

    public static function luminixSaved($callback)
    {
        static::registerModelEvent('luminixSaved', $callback);
    }

    public static function luminixDeleting($callback)
    {
        static::registerModelEvent('luminixDeleting', $callback);
    }

    public static function luminixDeleted($callback)
    {
        static::registerModelEvent('luminixDeleted', $callback);
    }

    public static function luminixRestoring($callback)
    {
        static::registerModelEvent('luminixRestoring', $callback);
    }

    public static function luminixRestored($callback)
    {
        static::registerModelEvent('luminixRestored', $callback);
    }

}