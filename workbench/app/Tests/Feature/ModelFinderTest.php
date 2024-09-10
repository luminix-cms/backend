<?php

namespace Workbench\App\Tests\Feature;

use Luminix\Backend\Facades\Finder;
use Workbench\App\Tests\TestCase;
use Workbench\App\Models\User;

class ModelFinderTest extends TestCase
{
    public function test_it_can_find_models()
    {
        $models = Finder::all();

        $this->assertEquals([
            'user' => 'Workbench\App\Models\User',
            'to_do' => 'Workbench\App\Models\ToDo',
            'category' => 'Workbench\App\Models\Category',
        ], $models->toArray());

    }
}
