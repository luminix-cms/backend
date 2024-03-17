<?php

namespace Luminix\Backend\Tests;

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
            \Workbench\App\Providers\FortifyServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('app.debug', true);
        // $app['config']->set('luminix.backend', require __DIR__ . '/../skeleton/config/backend.php');
        // $app['config']->set('fortify', require __DIR__ . '/../skeleton/config/fortify.php');
        // $app['config']->set('sanctum', require __DIR__ . '/../skeleton/config/sanctum.php');
        // $app['config']->set('auth', require __DIR__ . '/../skeleton/config/auth.php');
    }



    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

}