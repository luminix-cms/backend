<?php

namespace Workbench\App\Tests\Feature;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Workbench\App\Models\Post;
use Workbench\App\Models\ToDo;
use Workbench\App\Models\User;
use Workbench\App\Tests\TestCase;

/**
 * `destroyMany` and `restoreMany` write only the rows that passed the gate,
 * picked by key. A scope that narrows the `select()` to columns without the
 * key must not leave those rows unmatched.
 */
class ScopedSelectBulkWriteTest extends TestCase
{
    protected User $user;

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

    protected function setUp(): void
    {
        parent::setUp();

        Gate::define('read-post',   fn (?User $user) => !!$user);
        Gate::define('update-post', fn (?User $user) => !!$user);
        Gate::define('delete-post', fn (?User $user) => !!$user);

        $this->user = User::where('email', 'user@example.com')->first();
        $this->actingAs($this->user);
    }

    protected function tearDown(): void
    {
        // Drops the global scopes added below, kept in a static on Model.
        Model::clearBootedModels();

        parent::tearDown();
    }

    public function test_destroy_many_deletes_when_a_scope_selects_without_the_key()
    {
        $first = $this->user->toDos()->create(['title' => 'T1', 'description' => 'D1']);
        $second = $this->user->toDos()->create(['title' => 'T2', 'description' => 'D2']);

        ToDo::addGlobalScope('columns', fn (Builder $query) => $query->select('to_dos.title', 'to_dos.user_id'));

        $this->json('DELETE', '/luminix-api/to-dos', ['ids' => [$first->id, $second->id]])
            ->assertStatus(204);

        $this->assertDatabaseMissing('to_dos', ['id' => $first->id]);
        $this->assertDatabaseMissing('to_dos', ['id' => $second->id]);
    }

    public function test_restore_many_restores_when_a_scope_selects_without_the_key()
    {
        $first = Post::create(['title' => 'P1', 'body' => 'b', 'user_id' => $this->user->id]);
        $second = Post::create(['title' => 'P2', 'body' => 'b', 'user_id' => $this->user->id]);
        $first->delete();
        $second->delete();

        Post::addGlobalScope('columns', fn (Builder $query) => $query->select('posts.title', 'posts.user_id'));

        $this->json('POST', '/luminix-api/posts/restore', ['ids' => [$first->id, $second->id]])
            ->assertStatus(204);

        $this->assertDatabaseHas('posts', ['id' => $first->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('posts', ['id' => $second->id, 'deleted_at' => null]);
    }
}
