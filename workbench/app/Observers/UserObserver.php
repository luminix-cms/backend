<?php

namespace Workbench\App\Observers;

use Illuminate\Support\Facades\App;
use Workbench\App\Models\User;
use Workbench\App\Services\MockedService;

class UserObserver
{
    public function luminixCreating(User $user)
    {
        $service = App::make(MockedService::class);

        $service->method1();
    }
}