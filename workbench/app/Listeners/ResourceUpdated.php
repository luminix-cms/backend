<?php

namespace Workbench\App\Listeners;

use Illuminate\Support\Facades\App;
use Luminix\Backend\Events\UpdatedResource;
use Workbench\App\Services\MockedService;

class ResourceUpdated
{
    public function handle(UpdatedResource $event)
    {
        $service = App::make(MockedService::class);

        $service->updatedUser($event->model);
    }
}
