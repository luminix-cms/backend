<?php

namespace Luminix\Backend\Tests;

use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

class TestCase extends TestbenchTestCase
{
    use WithWorkbench;

    protected function getPackageProviders($app)
    {
        

        return [\Luminix\Backend\BackendServiceProvider::class];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('luminix.backend', require __DIR__ . '/../config/backend.ci.php');
    }
}