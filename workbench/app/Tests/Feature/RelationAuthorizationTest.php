<?php

namespace Workbench\App\Tests\Feature;

use Workbench\App\Models\Category;
use Workbench\App\Models\ToDo;
use Workbench\App\Models\User;
use Workbench\App\Tests\TestCase;

/**
 * sync/attach/detach are authorized as an *update of the parent model*:
 * the parent lookup applies `scopeAllowed('update')` (hidden row → 404,
 * before any write) and the `update-{alias}` gate (denial → 401), exactly
 * like the update endpoint.
 */
class RelationAuthorizationTest extends TestCase
{
    protected function createForeignToDo(): ToDo
    {
        $this->json('POST', '/luminix-api/users', [
            'name'                  => 'Other',
            'email'                 => 'other@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
        ]);

        return User::where('email', 'other@example.com')->first()
            ->toDos()->create(['title' => 'Foreign', 'description' => 'desc']);
    }

    public function test_guest_cannot_attach_to_a_todo()
    {
        $todo = $this->createForeignToDo();
        $category = Category::create(['name' => 'Cat A']);

        $this->json('POST', "/luminix-api/to-dos/{$todo->id}/categories/{$category->id}")
            ->assertStatus(404);

        $this->assertDatabaseMissing('category_to_do', ['to_do_id' => $todo->id]);
    }

    public function test_user_cannot_attach_categories_to_anothers_todo()
    {
        $todo = $this->createForeignToDo();
        $category = Category::create(['name' => 'Cat A']);

        $this->actingAs(User::where('email', 'user@example.com')->first());

        $this->json('POST', "/luminix-api/to-dos/{$todo->id}/categories/{$category->id}")
            ->assertStatus(404);

        $this->assertDatabaseMissing('category_to_do', ['to_do_id' => $todo->id]);
    }

    public function test_user_cannot_detach_categories_from_anothers_todo()
    {
        $todo = $this->createForeignToDo();
        $category = Category::create(['name' => 'Cat A']);
        $todo->categories()->attach($category->id);

        $this->actingAs(User::where('email', 'user@example.com')->first());

        $this->json('DELETE', "/luminix-api/to-dos/{$todo->id}/categories/{$category->id}")
            ->assertStatus(404);

        $this->assertDatabaseHas('category_to_do', [
            'to_do_id'    => $todo->id,
            'category_id' => $category->id,
        ]);
    }

    public function test_user_cannot_sync_categories_of_anothers_todo()
    {
        $todo = $this->createForeignToDo();
        $category = Category::create(['name' => 'Cat A']);

        $this->actingAs(User::where('email', 'user@example.com')->first());

        $this->json('POST', "/luminix-api/to-dos/{$todo->id}/categories/sync", [$category->id])
            ->assertStatus(404);

        $this->assertDatabaseMissing('category_to_do', ['to_do_id' => $todo->id]);
    }

    public function test_owner_can_attach_sync_and_detach_own_todo()
    {
        $user = User::where('email', 'user@example.com')->first();
        $this->actingAs($user);

        $todo = $user->toDos()->create(['title' => 'Mine', 'description' => 'desc']);
        $catA = Category::create(['name' => 'Cat A']);
        $catB = Category::create(['name' => 'Cat B']);

        $this->json('POST', "/luminix-api/to-dos/{$todo->id}/categories/{$catA->id}")
            ->assertStatus(200)
            ->assertJson(['id' => $todo->id]);

        $this->assertDatabaseHas('category_to_do', [
            'to_do_id'    => $todo->id,
            'category_id' => $catA->id,
        ]);

        $this->json('POST', "/luminix-api/to-dos/{$todo->id}/categories/sync", [$catB->id])
            ->assertStatus(200);

        $this->assertDatabaseMissing('category_to_do', [
            'to_do_id'    => $todo->id,
            'category_id' => $catA->id,
        ]);
        $this->assertDatabaseHas('category_to_do', [
            'to_do_id'    => $todo->id,
            'category_id' => $catB->id,
        ]);

        $this->json('DELETE', "/luminix-api/to-dos/{$todo->id}/categories/{$catB->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('category_to_do', ['to_do_id' => $todo->id]);
    }
}
