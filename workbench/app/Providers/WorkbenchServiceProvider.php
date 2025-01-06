<?php

namespace Workbench\App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Workbench\App\Models\Category;
use Workbench\App\Models\ToDo;
use Workbench\App\Models\User;

class WorkbenchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        

    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // User rules
        Gate::define('read-user', [$this, 'isUserReferencingItself']);
        Gate::define('update-user', [$this, 'isUserReferencingItself']);
        Gate::define('delete-user', [$this, 'isUserReferencingItself']);
        Gate::define('create-user', [$this, 'allowAny']);

        // ToDo rules
        Gate::define('read-to_do', [$this, 'isUserOwnerOfToDo']);
        Gate::define('update-to_do', [$this, 'isUserOwnerOfToDo']);
        Gate::define('delete-to_do', [$this, 'isUserOwnerOfToDo']);
        Gate::define('create-to_do', [$this, 'isAuthenticated']);

        // Category rules
        Gate::define('read-category', [$this, 'isAuthenticated']);;
    }

    public function isUserReferencingItself(?User $currentUser, User $targetUser): bool
    {
        return !!$currentUser && $currentUser->id === $targetUser->id;
    }

    public function isUserOwnerOfToDo(?User $user, ToDo $toDo): bool
    {
        return !!$user && $user->id === $toDo->user_id;
    }

    public function isAuthenticated(?User $user): bool
    {
        return !!$user;
    }

    public function allowAny(?User $user): bool
    {
        return true;
    }
}
