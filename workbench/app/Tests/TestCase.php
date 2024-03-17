<?php

namespace Workbench\App\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as TestbenchTestCase;
use Workbench\Database\Seeders\DatabaseSeeder;

class TestCase extends TestbenchTestCase
{

    use WithWorkbench;
    use RefreshDatabase;

    protected function getPackageProviders($app)
    {
        

        return [
            \Luminix\Backend\BackendServiceProvider::class,
            \Workbench\App\Providers\WorkbenchServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('app.debug', true);
        $app['config']->set('luminix.backend.security.middleware', ['web']);
        $app['config']->set('luminix.backend.models.include', [
            'Workbench\App\Models\User',
            'Workbench\App\Models\ToDo',
        ]);
        $app['config']->set('luminix.backend.api.controller_overrides', [
            'Workbench\App\Models\ToDo' => 'Workbench\App\Http\Controllers\ToDoController',
        ]);
        $app['config']->set('auth', require __DIR__.'/../../config/auth.ci.php');
    }



    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

}