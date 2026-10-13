<?php

namespace Workbench\App\Tests\Feature;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Workbench\App\Http\Controllers\ToDoController;
use Workbench\App\Models\Category;
use Workbench\App\Models\Tag;
use Workbench\App\Models\ToDo;
use Workbench\App\Models\User;
use Workbench\App\Tests\TestCase;

/**
 * `attach` and `sync` write only the pivot columns the relation declares with
 * `withPivot()`. ToDo::categories declares `note` and `withTimestamps()`; the
 * `category_to_do` table also has an undeclared `approved` column.
 */
class PivotAttributesTest extends TestCase
{
    protected User $user;

    protected ToDo $todo;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::where('email', 'user@example.com')->first();
        $this->actingAs($this->user);

        $this->todo = $this->user->toDos()->create(['title' => 'Mine', 'description' => 'desc']);
        $this->category = Category::create(['name' => 'Cat A']);
    }

    protected function createForeignToDo(): ToDo
    {
        $other = User::forceCreate([
            'name'     => 'Other',
            'email'    => 'other@example.com',
            'password' => 'password',
        ]);

        return $other->toDos()->create(['title' => 'Foreign', 'description' => 'desc']);
    }

    public function test_attach_writes_declared_pivot_columns()
    {
        $this->json('POST', "/luminix-api/to-dos/{$this->todo->id}/categories/{$this->category->id}", [
            'note' => 'from client',
        ])->assertStatus(200);

        $this->assertDatabaseHas('category_to_do', [
            'to_do_id'    => $this->todo->id,
            'category_id' => $this->category->id,
            'note'        => 'from client',
        ]);
    }

    public function test_attach_discards_undeclared_pivot_columns()
    {
        $this->json('POST', "/luminix-api/to-dos/{$this->todo->id}/categories/{$this->category->id}", [
            'note'     => 'from client',
            'approved' => true,
        ])->assertStatus(200);

        $this->assertDatabaseHas('category_to_do', [
            'to_do_id'    => $this->todo->id,
            'category_id' => $this->category->id,
            'note'        => 'from client',
            'approved'    => 0,
        ]);
    }

    public function test_attach_keeps_the_row_on_the_requested_parent()
    {
        $foreign = $this->createForeignToDo();

        $this->json('POST', "/luminix-api/to-dos/{$this->todo->id}/categories/{$this->category->id}", [
            'to_do_id' => $foreign->id,
        ])->assertStatus(200);

        $this->assertDatabaseHas('category_to_do', [
            'to_do_id'    => $this->todo->id,
            'category_id' => $this->category->id,
        ]);
        $this->assertDatabaseMissing('category_to_do', ['to_do_id' => $foreign->id]);
    }

    public function test_attach_keeps_the_morph_type_of_the_requested_parent()
    {
        $tag = Tag::forceCreate(['name' => 'Tag A']);

        $this->json('POST', "/luminix-api/to-dos/{$this->todo->id}/tags/{$tag->id}", [
            'taggable_type' => User::class,
        ])->assertStatus(200);

        $this->assertDatabaseHas('taggables', [
            'tag_id'        => $tag->id,
            'taggable_id'   => $this->todo->id,
            'taggable_type' => $this->todo->getMorphClass(),
        ]);
        $this->assertDatabaseMissing('taggables', ['taggable_type' => User::class]);
    }

    public function test_attach_does_not_take_pivot_timestamps_from_the_client()
    {
        $this->json('POST', "/luminix-api/to-dos/{$this->todo->id}/categories/{$this->category->id}", [
            'created_at' => '2000-01-01 00:00:00',
        ])->assertStatus(200);

        $this->assertDatabaseHas('category_to_do', [
            'to_do_id'    => $this->todo->id,
            'category_id' => $this->category->id,
        ]);
        $this->assertDatabaseMissing('category_to_do', ['created_at' => '2000-01-01 00:00:00']);
    }

    public function test_sync_writes_declared_and_discards_undeclared_pivot_columns()
    {
        $foreign = $this->createForeignToDo();

        $this->json('POST', "/luminix-api/to-dos/{$this->todo->id}/categories/sync", [
            [
                'id'       => $this->category->id,
                'note'     => 'from client',
                'approved' => true,
                'to_do_id' => $foreign->id,
            ],
        ])->assertStatus(200);

        $this->assertDatabaseHas('category_to_do', [
            'to_do_id'    => $this->todo->id,
            'category_id' => $this->category->id,
            'note'        => 'from client',
            'approved'    => 0,
        ]);
        $this->assertDatabaseMissing('category_to_do', ['to_do_id' => $foreign->id]);
    }

    public function test_sync_discards_undeclared_pivot_columns_on_an_existing_row()
    {
        $foreign = $this->createForeignToDo();
        $this->todo->categories()->attach($this->category->id, ['note' => 'before']);

        $this->json('POST', "/luminix-api/to-dos/{$this->todo->id}/categories/sync", [
            [
                'id'       => $this->category->id,
                'note'     => 'after',
                'approved' => true,
                'to_do_id' => $foreign->id,
            ],
        ])->assertStatus(200);

        $this->assertDatabaseHas('category_to_do', [
            'to_do_id'    => $this->todo->id,
            'category_id' => $this->category->id,
            'note'        => 'after',
            'approved'    => 0,
        ]);
        $this->assertDatabaseMissing('category_to_do', ['to_do_id' => $foreign->id]);
    }

    public function test_controller_can_set_pivot_attributes_itself()
    {
        $this->app->bind(ToDoController::class, fn () => new class extends ToDoController {
            protected function pivotAttributes(BelongsToMany $relation, array $attributes): array
            {
                return [...parent::pivotAttributes($relation, $attributes), 'approved' => true];
            }
        });

        $this->json('POST', "/luminix-api/to-dos/{$this->todo->id}/categories/{$this->category->id}", [
            'note' => 'from client',
        ])->assertStatus(200);

        $this->assertDatabaseHas('category_to_do', [
            'to_do_id'    => $this->todo->id,
            'category_id' => $this->category->id,
            'note'        => 'from client',
            'approved'    => 1,
        ]);
    }

    public function test_controller_pivot_attributes_apply_to_plain_ids_on_sync()
    {
        $this->app->bind(ToDoController::class, fn () => new class extends ToDoController {
            protected function pivotAttributes(BelongsToMany $relation, array $attributes): array
            {
                return [...parent::pivotAttributes($relation, $attributes), 'approved' => true];
            }
        });

        $this->json('POST', "/luminix-api/to-dos/{$this->todo->id}/categories/sync", [
            $this->category->id,
        ])->assertStatus(200);

        $this->assertDatabaseHas('category_to_do', [
            'to_do_id'    => $this->todo->id,
            'category_id' => $this->category->id,
            'approved'    => 1,
        ]);
    }
}
