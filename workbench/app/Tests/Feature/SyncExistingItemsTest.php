<?php

namespace Workbench\App\Tests\Feature;

use Illuminate\Support\Facades\Validator;
use Workbench\App\Models\Category;
use Workbench\App\Models\ToDo;
use Workbench\App\Models\User;
use Workbench\App\Tests\TestCase;

/**
 * `sync` checks every key against the related model's `scopeAllowed()`, except the keys the
 * parent already holds: resending the current collection must not fail on a row the caller
 * cannot see, while a new row outside the scope is still refused.
 */
class SyncExistingItemsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Category::$hiddenIds = [];

        $this->actingAs(User::where('email', 'user@example.com')->first());
    }

    protected function tearDown(): void
    {
        Category::$hiddenIds = [];

        parent::tearDown();
    }

    protected function ownToDo(): ToDo
    {
        return auth()->user()->toDos()->create([
            'title'       => 'Mine',
            'description' => 'desc',
        ]);
    }

    protected function hiddenCategory(): Category
    {
        $category = Category::create(['name' => 'Hidden']);

        Category::$hiddenIds = [$category->id];

        return $category;
    }

    public function test_resending_the_current_collection_keeps_a_hidden_item()
    {
        $todo = $this->ownToDo();
        $visible = Category::create(['name' => 'Visible']);
        $hidden = $this->hiddenCategory();
        $todo->categories()->attach([$visible->id, $hidden->id]);

        $this->json('POST', "/luminix-api/to-dos/{$todo->id}/categories/sync", [$visible->id, $hidden->id])
            ->assertStatus(200);

        $this->assertDatabaseHas('category_to_do', ['to_do_id' => $todo->id, 'category_id' => $visible->id]);
        $this->assertDatabaseHas('category_to_do', ['to_do_id' => $todo->id, 'category_id' => $hidden->id]);
    }

    public function test_resending_a_hidden_item_as_a_pivot_row_keeps_it()
    {
        $todo = $this->ownToDo();
        $hidden = $this->hiddenCategory();
        $todo->categories()->attach($hidden->id);

        $this->json('POST', "/luminix-api/to-dos/{$todo->id}/categories/sync", [['id' => $hidden->id]])
            ->assertStatus(200);

        $this->assertDatabaseHas('category_to_do', ['to_do_id' => $todo->id, 'category_id' => $hidden->id]);
    }

    public function test_a_new_hidden_item_is_still_refused()
    {
        $todo = $this->ownToDo();
        $visible = Category::create(['name' => 'Visible']);
        $todo->categories()->attach($visible->id);
        $hidden = $this->hiddenCategory();

        $this->json('POST', "/luminix-api/to-dos/{$todo->id}/categories/sync", [$visible->id, $hidden->id])
            ->assertStatus(422);

        $this->assertDatabaseHas('category_to_do', ['to_do_id' => $todo->id, 'category_id' => $visible->id]);
        $this->assertDatabaseMissing('category_to_do', ['to_do_id' => $todo->id, 'category_id' => $hidden->id]);
    }

    public function test_an_item_hidden_but_held_by_another_parent_is_still_refused()
    {
        $todo = $this->ownToDo();
        $other = $this->ownToDo();
        $hidden = $this->hiddenCategory();
        $other->categories()->attach($hidden->id);

        $this->json('POST', "/luminix-api/to-dos/{$todo->id}/categories/sync", [$hidden->id])
            ->assertStatus(422);

        $this->assertDatabaseMissing('category_to_do', ['to_do_id' => $todo->id, 'category_id' => $hidden->id]);
    }

    public function test_omitting_a_hidden_item_still_removes_it()
    {
        $todo = $this->ownToDo();
        $visible = Category::create(['name' => 'Visible']);
        $hidden = $this->hiddenCategory();
        $todo->categories()->attach([$visible->id, $hidden->id]);

        $this->json('POST', "/luminix-api/to-dos/{$todo->id}/categories/sync", [$visible->id])
            ->assertStatus(200);

        $this->assertDatabaseHas('category_to_do', ['to_do_id' => $todo->id, 'category_id' => $visible->id]);
        $this->assertDatabaseMissing('category_to_do', ['to_do_id' => $todo->id, 'category_id' => $hidden->id]);
    }

    public function test_the_rule_without_a_parent_key_checks_the_scope_alone()
    {
        $todo = $this->ownToDo();
        $hidden = $this->hiddenCategory();
        $todo->categories()->attach($hidden->id);

        $withoutParent = Validator::make([$hidden->id], [
            '*' => 'luminix_sync:' . ToDo::class . ',categories',
        ]);
        $withParent = Validator::make([$hidden->id], [
            '*' => 'luminix_sync:' . ToDo::class . ',categories,' . $todo->id,
        ]);

        $this->assertTrue($withoutParent->fails());
        $this->assertTrue($withParent->passes());
    }
}
