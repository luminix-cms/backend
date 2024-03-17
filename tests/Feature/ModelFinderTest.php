<?php

namespace Luminix\Backend\Tests\Feature;

use Luminix\Backend\Services\ModelFinder;
use Luminix\Backend\Tests\TestCase;
use Workbench\App\Models\User;

class ModelFinderTest extends TestCase
{
    public function test_it_can_find_models()
    {
        $models = $this->app->make(ModelFinder::class)->all();

        $this->assertEquals([
            'user' => 'Workbench\App\Models\User',
            'to_do' => 'Workbench\App\Models\ToDo',
        ], $models->toArray());

    }
}
