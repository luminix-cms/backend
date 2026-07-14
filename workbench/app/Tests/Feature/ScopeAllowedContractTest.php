<?php

namespace Workbench\App\Tests\Feature;

use Illuminate\Support\Facades\Gate;
use Workbench\App\Models\Category;
use Workbench\App\Models\Post;
use Workbench\App\Models\ToDo;
use Workbench\App\Models\User;
use Workbench\App\Tests\TestCase;

/**
 * Pins which permission string `scopeAllowed()` receives on each endpoint,
 * so consumers can rely on it when writing scope implementations:
 *
 * | endpoint              | scopeAllowed receives  | when                  |
 * |-----------------------|------------------------|-----------------------|
 * | index / show          | 'read'                 | listing / item lookup |
 * | store                 | — (never called)       |                       |
 * | update                | 'update'               | pre-write lookup only |
 * | destroy / destroyMany | 'delete'               | pre-write lookup      |
 * | restoreMany           | 'update'               | pre-write lookup      |
 * | sync / attach / detach| 'update'               | parent lookup only    |
 *
 * Post-write response fetches are unscoped (see CommittedWriteResponseTest).
 */
class ScopeAllowedContractTest extends TestCase
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

    protected function setUp(): void
    {
        parent::setUp();

        Gate::define('create-post', fn (?User $user) => !!$user);
        Gate::define('read-post',   fn (?User $user) => !!$user);
        Gate::define('update-post', fn (?User $user) => !!$user);
        Gate::define('delete-post', fn (?User $user) => !!$user);

        $this->actingAs(User::where('email', 'user@example.com')->first());
    }

    protected function ownToDo(): ToDo
    {
        return auth()->user()->toDos()->create([
            'title'       => 'Mine',
            'description' => 'desc',
        ]);
    }

    protected function resetRecorded(): void
    {
        ToDo::$receivedPermissions = [];
        Post::$receivedPermissions = [];
    }

    public function test_index_applies_read_scope()
    {
        $this->resetRecorded();

        $this->json('GET', '/luminix-api/to-dos')->assertStatus(200);

        $this->assertSame(['read'], ToDo::$receivedPermissions);
    }

    public function test_show_applies_read_scope()
    {
        $todo = $this->ownToDo();
        $this->resetRecorded();

        $this->json('GET', "/luminix-api/to-dos/{$todo->id}")->assertStatus(200);

        $this->assertSame(['read'], ToDo::$receivedPermissions);
    }

    public function test_store_never_calls_scope_allowed()
    {
        $this->resetRecorded();

        $this->json('POST', '/luminix-api/to-dos', [
            'title'       => 'New',
            'description' => 'desc',
        ])->assertStatus(201);

        $this->assertSame([], ToDo::$receivedPermissions);
    }

    public function test_update_applies_update_scope_on_the_pre_write_lookup_only()
    {
        $todo = $this->ownToDo();
        $this->resetRecorded();

        $this->json('POST', "/luminix-api/to-dos/{$todo->id}", [
            'title' => 'Renamed',
        ])->assertStatus(200);

        $this->assertSame(['update'], ToDo::$receivedPermissions);
    }

    public function test_destroy_applies_delete_scope()
    {
        $todo = $this->ownToDo();
        $this->resetRecorded();

        $this->json('DELETE', "/luminix-api/to-dos/{$todo->id}")->assertStatus(204);

        $this->assertSame(['delete'], ToDo::$receivedPermissions);
    }

    public function test_destroy_many_applies_delete_scope()
    {
        $t1 = $this->ownToDo();
        $t2 = $this->ownToDo();
        $this->resetRecorded();

        $this->json('DELETE', '/luminix-api/to-dos', ['ids' => [$t1->id, $t2->id]])
            ->assertStatus(204);

        $this->assertSame(['delete'], ToDo::$receivedPermissions);
    }

    public function test_restore_many_applies_update_scope()
    {
        $post = Post::create([
            'title'   => 'Restorable',
            'body'    => 'body',
            'user_id' => auth()->id(),
        ]);
        $post->delete();
        $this->resetRecorded();

        $this->json('POST', '/luminix-api/posts/restore', ['ids' => [$post->id]])
            ->assertStatus(204);

        $this->assertSame(['update'], Post::$receivedPermissions);
    }

    public function test_relation_endpoints_apply_update_scope_on_the_parent_lookup_only()
    {
        $todo = $this->ownToDo();
        $category = Category::create(['name' => 'Cat A']);

        $this->resetRecorded();
        $this->json('POST', "/luminix-api/to-dos/{$todo->id}/categories/{$category->id}")
            ->assertStatus(200);
        $this->assertSame(['update'], ToDo::$receivedPermissions, 'attach');

        $this->resetRecorded();
        $this->json('POST', "/luminix-api/to-dos/{$todo->id}/categories/sync", [$category->id])
            ->assertStatus(200);
        $this->assertSame(['update'], ToDo::$receivedPermissions, 'sync');

        $this->resetRecorded();
        $this->json('DELETE', "/luminix-api/to-dos/{$todo->id}/categories/{$category->id}")
            ->assertStatus(200);
        $this->assertSame(['update'], ToDo::$receivedPermissions, 'detach');
    }
}
