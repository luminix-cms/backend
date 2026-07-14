<?php

namespace Workbench\App\Tests\Feature;

use Workbench\App\Models\User;
use Workbench\App\Tests\TestCase;

/**
 * A committed write must never produce a 404 response.
 *
 * `store()` and `update()` re-fetch the item after the transaction to build
 * the response. If the model's `scopeAllowed()` hides the row from its own
 * creator/updater, the write is committed but the client receives a 404 —
 * and a client that retries on error will duplicate the write.
 */
class CommittedWriteResponseTest extends TestCase
{
    protected function createOtherUser(): User
    {
        $this->json('POST', '/luminix-api/users', [
            'name'                  => 'Other',
            'email'                 => 'other@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
        ]);

        return User::where('email', 'other@example.com')->first();
    }

    public function test_store_responds_201_when_scope_allowed_hides_the_created_row()
    {
        $other = $this->createOtherUser();

        $this->actingAs(User::where('email', 'user@example.com')->first());

        // ToDo::scopeAllowed limits every query to auth()->id(), so a todo
        // created for another user is invisible to its own creator.
        $response = $this->json('POST', '/luminix-api/to-dos', [
            'title'       => 'Delegated task',
            'description' => 'Created on behalf of another user',
            'user_id'     => $other->id,
        ]);

        // The row is committed regardless of the response status...
        $this->assertDatabaseHas('to_dos', [
            'title'   => 'Delegated task',
            'user_id' => $other->id,
        ]);

        // ...so the response must confirm the creation.
        $response->assertStatus(201)
            ->assertJson([
                'title'   => 'Delegated task',
                'user_id' => $other->id,
            ]);
    }

    public function test_update_responds_200_when_the_update_moves_the_row_out_of_scope()
    {
        $other = $this->createOtherUser();

        $user = User::where('email', 'user@example.com')->first();
        $this->actingAs($user);

        $todo = $user->toDos()->create([
            'title'       => 'Reassignable task',
            'description' => 'Owned by the acting user',
        ]);

        $response = $this->json('POST', "/luminix-api/to-dos/{$todo->id}", [
            'user_id' => $other->id,
        ]);

        $this->assertDatabaseHas('to_dos', [
            'id'      => $todo->id,
            'user_id' => $other->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'id'      => $todo->id,
                'user_id' => $other->id,
            ]);
    }
}
