<?php

namespace Workbench\App\Tests\Feature;

use Illuminate\Support\Facades\Gate;
use Illuminate\Testing\TestResponse;
use Workbench\App\Models\Post;
use Workbench\App\Models\User;
use Workbench\App\Tests\TestCase;

/**
 * A caller who can't read a record gets the same 404 a missing id gets, on
 * every route addressed by id. Answering 401 for an existing id and 404 for a
 * missing one would let the caller tell which ids exist. A caller who can read
 * the record but not perform the action still gets 401.
 */
class ItemAuthorizationTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('luminix.backend.models.include', [
            'Workbench\App\Models\User',
            'Workbench\App\Models\ToDo',
            'Workbench\App\Models\Category',
            'Workbench\App\Models\Tag',
            'Workbench\App\Models\Post',
        ]);
    }

    protected function createUser(string $name): User
    {
        return User::create([
            'name' => $name,
            'email' => strtolower($name) . '@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    protected function assertAnswersLikeMissing(TestResponse $denied, TestResponse $missing): void
    {
        $denied->assertStatus(404);
        $missing->assertStatus(404);
        $this->assertSame($missing->json('message'), $denied->json('message'));
    }

    public function test_show_answers_a_scope_hidden_id_like_a_missing_one()
    {
        $owner = $this->createUser('Owner');
        $todo = $owner->toDos()->create(['title' => 'Not yours', 'description' => 'desc']);

        $this->actingAs($this->createUser('Other'));

        $this->assertAnswersLikeMissing(
            $this->json('GET', "/luminix-api/to-dos/{$todo->id}"),
            $this->json('GET', '/luminix-api/to-dos/999999'),
        );
    }

    public function test_gate_only_model_answers_a_denied_id_like_a_missing_one()
    {
        // User has no scopeAllowed: only the Gate stands between Other and user 1.
        $this->actingAs($this->createUser('Other'));

        foreach (['GET', 'POST', 'DELETE'] as $method) {
            $this->assertAnswersLikeMissing(
                $this->json($method, '/luminix-api/users/1'),
                $this->json($method, '/luminix-api/users/999999'),
            );
        }

        $this->assertAnswersLikeMissing(
            $this->json('DELETE', '/luminix-api/users', ['ids' => [1]]),
            $this->json('DELETE', '/luminix-api/users', ['ids' => [999999]]),
        );

        $this->assertDatabaseHas('users', ['id' => 1]);
    }

    public function test_destroy_many_skips_ids_the_caller_cannot_read()
    {
        $other = $this->createUser('Other');
        $this->actingAs($other);

        $this->json('DELETE', '/luminix-api/users', ['ids' => [$other->id, 1]])
            ->assertStatus(204);

        $this->assertDatabaseMissing('users', ['id' => $other->id]);
        $this->assertDatabaseHas('users', ['id' => 1]);
    }

    public function test_denied_write_on_a_readable_record_answers_401()
    {
        Gate::define('read-post', fn (?User $user) => true);
        Gate::define('update-post', fn (?User $user) => false);
        Gate::define('delete-post', fn (?User $user) => false);

        $user = User::where('email', 'user@example.com')->first();
        $this->actingAs($user);

        $post = Post::create(['title' => 'Post', 'body' => 'body', 'user_id' => $user->id]);

        $this->json('POST', "/luminix-api/posts/{$post->id}", ['title' => 'Changed'])->assertStatus(401);
        $this->json('DELETE', "/luminix-api/posts/{$post->id}")->assertStatus(401);
        $this->json('DELETE', '/luminix-api/posts', ['ids' => [$post->id]])->assertStatus(401);

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => 'Post', 'deleted_at' => null]);
    }

    public function test_denied_write_on_an_unreadable_record_answers_like_a_missing_one()
    {
        Gate::define('read-post', fn (?User $user) => false);
        Gate::define('update-post', fn (?User $user) => false);

        $user = User::where('email', 'user@example.com')->first();
        $this->actingAs($user);

        $post = Post::create(['title' => 'Post', 'body' => 'body', 'user_id' => $user->id]);

        $this->assertAnswersLikeMissing(
            $this->json('POST', "/luminix-api/posts/{$post->id}", ['title' => 'Changed']),
            $this->json('POST', '/luminix-api/posts/999999', ['title' => 'Changed']),
        );

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => 'Post']);
    }
}
