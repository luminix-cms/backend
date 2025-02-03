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

        $service->creatingUser($user);
    }

    public function luminixCreated(User $user)
    {
        $service = App::make(MockedService::class);

        $service->createdUser($user);
    }

    public function luminixUpdating(User $user)
    {
        $service = App::make(MockedService::class);

        $service->updatingUser($user);
    }

    // public function luminixUpdated(User $user)
    // {
    //     $service = App::make(MockedService::class);

    //     $service->updatedUser($user);
    // }

    public function luminixDeleting(User $user)
    {
        $service = App::make(MockedService::class);

        $service->deletingUser($user);
    }

    public function luminixDeleted(User $user)
    {
        $service = App::make(MockedService::class);

        $service->deletedUser($user);
    }

    public function luminixSaving(User $user)
    {
        $service = App::make(MockedService::class);

        $service->savingUser($user);
    }

    public function luminixSaved(User $user)
    {
        $service = App::make(MockedService::class);

        $service->savedUser($user);
    }

}