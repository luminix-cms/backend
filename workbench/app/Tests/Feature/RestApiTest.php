<?php

namespace Workbench\App\Tests\Feature;

use Workbench\App\Tests\TestCase;
use Workbench\App\Models\User;

class RestApiTest extends TestCase
{
    public function test_apis_are_protected()
    {

        $this->json('GET', '/luminix-api/users')
            ->assertStatus(401);

        $this->json('GET', '/luminix-api/users/1')
            ->assertStatus(401);

        $this->json('PUT', '/luminix-api/users/1')
            ->assertStatus(401);

        $this->json('DELETE', '/luminix-api/users/1')
            ->assertStatus(401);

        $this->json('GET', '/luminix-api/to_dos')
            ->assertStatus(401);

        $this->json('GET', '/luminix-api/to_dos/1')
            ->assertStatus(401);
        
        $this->json('POST', '/luminix-api/to_dos')
            ->assertStatus(401);
        
        $this->json('PUT', '/luminix-api/to_dos/1')
            ->assertStatus(401);

        $this->json('DELETE', '/luminix-api/to_dos/1')
            ->assertStatus(401);

        $this->json('GET', '/luminix-api/categories')
            ->assertStatus(401);

        $this->json('GET', '/luminix-api/categories/1')
            ->assertStatus(401);

        $this->json('POST', '/luminix-api/categories')
            ->assertStatus(401);

        $this->json('PUT', '/luminix-api/categories/1')
            ->assertStatus(401);
        
        $this->json('DELETE', '/luminix-api/categories/1')
            ->assertStatus(401);

    }

    public function test_users_can_interact_with_apis()
    {
        // can create user
        $this->json('POST', '/luminix-api/users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertStatus(201);
        
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        // acting as John from now on
        $this->actingAs($user = User::where('email', 'john@example.com')->first());

        // cant read other users
        $this->json('GET', '/luminix-api/users')
            ->assertStatus(401);

        $this->json('GET', '/luminix-api/users/1')
            ->assertStatus(401);

        // can read self
        $this->json('GET', "/luminix-api/users/{$user->id}")
            ->assertStatus(200)
            ->assertJson([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);

        // can update self
        $this->json('PUT', "/luminix-api/users/{$user->id}", [
            'name' => 'Jane Doe',
        ])->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Jane Doe',
        ]);

        // cant read other users to_dos
        $this->json('GET', '/luminix-api/to_dos/1')
            ->assertStatus(401);

        // can create to_do
        $this->json('POST', '/luminix-api/to_dos', [
            'title' => 'Buy milk',
            'description' => 'Buy milk from the store',
        ])->assertStatus(201);

        $this->assertDatabaseHas('to_dos', [
            'title' => 'Buy milk',
            'description' => 'Buy milk from the store',
            'completed' => 0,
            'user_id' => $user->id,
        ]);

        // can read to_dos
        $response = $this->json('GET', '/luminix-api/to_dos');
        $response->assertStatus(200);

        $this->assertCount(1, $response->json('data'));

        $todoId = $response->json('data')[0]['id'];

        // can read self to_do
        $this->json('GET', "/luminix-api/to_dos/{$todoId}")
            ->assertStatus(200)
            ->assertJson([
                'id' => $user->toDos->first()->id,
                'title' => 'Buy milk',
                'description' => 'Buy milk from the store',
                'completed' => 0,
                'user_id' => $user->id,
            ]);

        // can update self to_do

        $this->json('PUT', "/luminix-api/to_dos/{$todoId}", [
            'completed' => 1,
        ])->assertStatus(200);

        $this->assertDatabaseHas('to_dos', [
            'id' => $user->toDos->first()->id,
            'completed' => 1,
        ]);

        // can add categories to self to_do
        $categories = collect($this->json('GET', '/luminix-api/categories')->json('data'));
        $selected = $categories->random(3)->pluck('id')->toArray();
        
        $this->json('POST', "/luminix-api/to_dos/{$todoId}/categories/sync", $selected)->assertStatus(200);

        $this->assertDatabaseHas('category_to_do', [
            'to_do_id' => $todoId,
            'category_id' => $selected[0],
        ]);

        $this->assertDatabaseHas('category_to_do', [
            'to_do_id' => $todoId,
            'category_id' => $selected[1],
        ]);

        $this->assertDatabaseHas('category_to_do', [
            'to_do_id' => $todoId,
            'category_id' => $selected[2],
        ]);

        // can detach categories from self to_do
        $this->json('POST', "/luminix-api/to_dos/{$todoId}/categories/detach", [$selected[0]])
            ->assertStatus(200);

        $this->assertDatabaseMissing('category_to_do', [
            'to_do_id' => $todoId,
            'category_id' => $selected[0],
        ]);

        // can attach categories to self to_do
        $this->json('POST', "/luminix-api/to_dos/{$todoId}/categories/attach", [$selected[0]])
            ->assertStatus(200);

        $this->assertDatabaseHas('category_to_do', [
            'to_do_id' => $todoId,
            'category_id' => $selected[0],
        ]);


        // can get minified to_dos
        $response = $this->json('GET', '/luminix-api/to_dos?minified=1');
        // Verify $labeledBy is used
        $response->assertJsonPath('0.title', 'Buy milk');
        $response->assertStatus(200);

        // can get minified categories
        $response = $this->json('GET', '/luminix-api/categories?minified=1');
        // Verify first fillable field is used
        $response->assertJsonPath('0.name', $categories[0]['name']);
        $response->assertStatus(200);

        // can delete self to_do
        $this->json('DELETE', "/luminix-api/to_dos/{$todoId}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('to_dos', [
            'id' => $todoId,
        ]);

    }
}

