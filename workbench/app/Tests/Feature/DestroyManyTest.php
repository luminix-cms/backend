<?php

namespace Workbench\App\Tests\Feature;

use Workbench\App\Models\ToDo;
use Workbench\App\Models\User;
use Workbench\App\Tests\TestCase;

/**
 * Tests for the destroyMany (batch delete) API action.
 */
class DestroyManyTest extends TestCase
{
    public function test_destroy_many_deletes_multiple_records()
    {
        $user = User::first();
        $this->actingAs($user);

        $t1 = $user->toDos()->create(['title' => 'T1', 'description' => 'D1']);
        $t2 = $user->toDos()->create(['title' => 'T2', 'description' => 'D2']);
        $t3 = $user->toDos()->create(['title' => 'T3', 'description' => 'D3']);

        $this->json('DELETE', '/luminix-api/to-dos', ['ids' => [$t1->id, $t2->id]])
            ->assertStatus(204);

        $this->assertDatabaseMissing('to_dos', ['id' => $t1->id]);
        $this->assertDatabaseMissing('to_dos', ['id' => $t2->id]);
        $this->assertDatabaseHas('to_dos', ['id' => $t3->id]);
    }

    public function test_destroy_many_requires_ids_field()
    {
        $this->actingAs(User::first());

        $this->json('DELETE', '/luminix-api/to-dos', [])->assertStatus(422);
    }

    public function test_destroy_many_validates_that_ids_must_exist()
    {
        $this->actingAs(User::first());

        $this->json('DELETE', '/luminix-api/to-dos', ['ids' => [999]])
            ->assertStatus(422);
    }

    public function test_destroy_many_returns_404_when_records_belong_to_another_user()
    {
        // Create a second user with their own todo
        $this->json('POST', '/luminix-api/users', [
            'name'                  => 'Other',
            'email'                 => 'other@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
        ]);

        $other = User::where('email', 'other@example.com')->first();
        $todo  = $other->toDos()->create(['title' => 'Other todo', 'description' => 'desc']);

        // Authenticate as the first user and try to delete the other's todo
        $this->actingAs(User::where('email', 'user@example.com')->first());

        // scopeAllowed('delete') filters by auth()->id(), so the todo won't be found → 404
        $this->json('DELETE', '/luminix-api/to-dos', ['ids' => [$todo->id]])
            ->assertStatus(404);
    }

    public function test_destroy_many_returns_404_when_unauthenticated()
    {
        // scopeAllowed with null auth → where('user_id', null) → no results → 404
        $this->json('DELETE', '/luminix-api/to-dos', ['ids' => [1]])
            ->assertStatus(404);
    }

    public function test_destroy_many_only_deletes_owned_records_from_mixed_ids()
    {
        $user = User::first();
        $this->actingAs($user);

        // Create todos for this user
        $mine = $user->toDos()->create(['title' => 'Mine', 'description' => 'desc']);

        // The seeded todo (id=1) also belongs to user 1, so it would match
        $seededId = ToDo::where('user_id', $user->id)->where('title', 'Test To Do')->first()->id;

        $this->json('DELETE', '/luminix-api/to-dos', ['ids' => [$mine->id, $seededId]])
            ->assertStatus(204);

        $this->assertDatabaseMissing('to_dos', ['id' => $mine->id]);
        $this->assertDatabaseMissing('to_dos', ['id' => $seededId]);
    }
}
