<?php

namespace Workbench\App\Tests\Feature;

use Workbench\App\Models\Category;
use Workbench\App\Models\ToDo;
use Workbench\App\Models\User;
use Workbench\App\Tests\TestCase;

class RelationshipTest extends TestCase
{
    public function test_model_relationships_are_read()
    {
        $user = new User();
        $toDo = new ToDo();
        $category = new Category();

        $this->assertEquals(
            [
                'to_dos' => [
                    'type' => 'HasMany',
                    'model' => 'to_do',
                ],
            ],
            $user->getRelationships()
        );

        $this->assertEquals(
            [
                'user' => [
                    'type' => 'BelongsTo',
                    'model' => 'user',
                ],
                'categories' => [
                    'type' => 'BelongsToMany',
                    'model' => 'category',
                ],
            ],
            $toDo->getRelationships()
        );

        $this->assertEquals(
            [
                'to_dos' => [
                    'type' => 'BelongsToMany',
                    'model' => 'to_do',
                ],
            ],
            $category->getRelationships()
        );

    }
}