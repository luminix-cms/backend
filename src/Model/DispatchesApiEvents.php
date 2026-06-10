<?php

namespace Luminix\Backend\Model;

use Illuminate\Support\Str;

trait DispatchesApiEvents {

    public function getObservableEvents()
    {
        return array_unique(array_merge(parent::getObservableEvents(), [
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
        ]));
    }

    public function initializeDispatchesApiEvents()
    {
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