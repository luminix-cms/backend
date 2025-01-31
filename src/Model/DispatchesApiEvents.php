<?php

namespace Luminix\Backend\Model;

use Illuminate\Support\Str;

trait DispatchesApiEvents {

    public function initializeDispatchesApiEvents()
    {
        $this->observables = array_merge($this->observables, [
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
        ]);

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

}