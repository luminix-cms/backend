<?php

namespace Workbench\App\Tests\Feature;

use Workbench\App\Models\Category;
use Workbench\App\Models\ToDo;
use Workbench\App\Models\User;
use Workbench\App\Tests\TestCase;

class RelationshipTest extends TestCase
{
    public function test_model_relationships_are_correct()
    {
        $user = new User();
        $toDo = new ToDo();
        $category = new Category();

        $this->assertEquals(
            [
                'to_dos' => [
                    'type' => 'HasMany',
                    'model' => 'to_do',
                    'foreignKey' => 'user_id',
                    'ownerKey' => null,
                ],
            ],
            $user->getRelationships()
        );

        $this->assertEquals(
            [
                'user' => [
                    'type' => 'BelongsTo',
                    'model' => 'user',
                    'foreignKey' => 'user_id',
                    'ownerKey' => 'id',
                ],
                'categories' => [
                    'type' => 'BelongsToMany',
                    'model' => 'category',
                    'foreignKey' => null,
                    'ownerKey' => null,
                ],
            ],
            $toDo->getRelationships()
        );

        $this->assertEquals(
            [
                'to_dos' => [
                    'type' => 'BelongsToMany',
                    'model' => 'to_do',
                    'foreignKey' => null,
                    'ownerKey' => null,
                ],
            ],
            $category->getRelationships()
        );

    }
}