<?php

namespace Workbench\App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
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
        Gate::define('read-user', function (?User $currentUser, ?User $user) {
            if ($currentUser && $user) {
                return $currentUser->id === $user->id;
            }
            return false;
        });

        Gate::define('update-user', function (?User $currentUser, ?User $user) {
            if ($currentUser && $user) {
                return $currentUser->id === $user->id;
            }
            return false;
        });

        Gate::define('delete-user', function (?User $currentUser, ?User $user) {
            if ($currentUser && $user) {
                return $currentUser->id === $user->id;
            }
            return false;
        });

        Gate::define('create-user', function (?User $currentUser, ?User $user) {
            return true;
        });

        Gate::define('read-to_do', function (?User $currentUser, ?ToDo $toDo) {
            if ($currentUser && $toDo) {
                return $currentUser->id === $toDo->user_id;
            }
            return !is_null($currentUser); 
            // if not logged in, return false
            // results will be filtered by scopeAllowed
        });

        Gate::define('update-to_do', function (?User $currentUser, ?ToDo $toDo) {
            if ($currentUser && $toDo) {
                return $currentUser->id === $toDo->user_id;
            }
            return false;
        });

        Gate::define('delete-to_do', function (?User $currentUser, ?ToDo $toDo) {
            if ($currentUser && $toDo) {
                return $currentUser->id === $toDo->user_id;
            }
            return false;
        });

        Gate::define('create-to_do', function (?User $currentUser, ?ToDo $toDo) {
            return !is_null($currentUser);
        });
    }
}
