<?php

namespace Workbench\App\Tests\Feature;

use Illuminate\Support\Facades\Gate;
use Workbench\App\Models\Post;
use Workbench\App\Models\User;
use Workbench\App\Tests\TestCase;

/**
 * Tests for soft delete, force delete, restore (single and batch),
 * the `tab=trashed` scope, and null/notNull filtering on a nullable column.
 *
 * The Post model uses SoftDeletes and is included only in this test class
 * to avoid affecting other test classes.
 */
class SoftDeleteTest extends TestCase
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
    }

    // ── Soft delete ──────────────────────────────────────────────────────────

    public function test_delete_soft_deletes_the_record()
    {
        $user = User::first();
        $this->actingAs($user);

        $post = Post::create(['title' => 'Soft delete me', 'body' => 'body', 'user_id' => $user->id]);

        $this->json('DELETE', "/luminix-api/posts/{$post->id}")->assertStatus(204);

        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }

    public function test_soft_deleted_record_does_not_appear_in_index()
    {
        $user = User::first();
        $this->actingAs($user);

        $post = Post::create(['title' => 'Hidden', 'body' => 'body', 'user_id' => $user->id]);
        $post->delete();

        $response = $this->json('GET', '/luminix-api/posts');
        $response->assertStatus(200);
        $ids = array_column($response->json('data'), 'id');
        $this->assertNotContains($post->id, $ids);
    }

    public function test_trashed_tab_shows_soft_deleted_records()
    {
        $user = User::first();
        $this->actingAs($user);

        $active  = Post::create(['title' => 'Active',  'body' => 'body', 'user_id' => $user->id]);
        $trashed = Post::create(['title' => 'Trashed', 'body' => 'body', 'user_id' => $user->id]);
        $trashed->delete();

        $response = $this->json('GET', '/luminix-api/posts?tab=trashed');
        $response->assertStatus(200);

        $ids = array_column($response->json('data'), 'id');
        $this->assertContains($trashed->id, $ids);
        $this->assertNotContains($active->id, $ids);
    }

    // ── Force delete ─────────────────────────────────────────────────────────

    public function test_force_delete_removes_record_permanently()
    {
        $user = User::first();
        $this->actingAs($user);

        $post = Post::create(['title' => 'Force delete me', 'body' => 'body', 'user_id' => $user->id]);

        $this->json('DELETE', "/luminix-api/posts/{$post->id}?force=1")->assertStatus(204);

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_force_delete_also_removes_soft_deleted_records()
    {
        $user = User::first();
        $this->actingAs($user);

        $post = Post::create(['title' => 'Already soft deleted', 'body' => 'body', 'user_id' => $user->id]);
        $post->delete();

        $this->json('DELETE', "/luminix-api/posts/{$post->id}?force=1")->assertStatus(204);

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    // ── Restore single ───────────────────────────────────────────────────────

    public function test_restore_via_update_endpoint_restores_soft_deleted_record()
    {
        $user = User::first();
        $this->actingAs($user);

        $post = Post::create(['title' => 'Restore me', 'body' => 'body', 'user_id' => $user->id]);
        $post->delete();

        $this->json('POST', "/luminix-api/posts/{$post->id}?restore=1")->assertStatus(200);

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'deleted_at' => null]);
    }

    public function test_restored_record_appears_in_index()
    {
        $user = User::first();
        $this->actingAs($user);

        $post = Post::create(['title' => 'Will come back', 'body' => 'body', 'user_id' => $user->id]);
        $post->delete();

        $this->json('POST', "/luminix-api/posts/{$post->id}?restore=1");

        $response = $this->json('GET', '/luminix-api/posts');
        $ids = array_column($response->json('data'), 'id');
        $this->assertContains($post->id, $ids);
    }

    // ── Restore events ───────────────────────────────────────────────────────

    public function test_restore_events_are_fired()
    {
        $user = User::first();
        $this->actingAs($user);

        $restoringFired = false;
        $restoredFired  = false;

        Post::luminixRestoring(function () use (&$restoringFired) {
            $restoringFired = true;
        });

        Post::luminixRestored(function () use (&$restoredFired) {
            $restoredFired = true;
        });

        $post = Post::create(['title' => 'Event test', 'body' => 'body', 'user_id' => $user->id]);
        $post->delete();

        $this->json('POST', "/luminix-api/posts/{$post->id}?restore=1")->assertStatus(200);

        $this->assertTrue($restoringFired, 'luminixRestoring was not fired');
        $this->assertTrue($restoredFired,  'luminixRestored was not fired');
    }

    // ── restoreMany ──────────────────────────────────────────────────────────

    public function test_restore_many_restores_multiple_soft_deleted_records()
    {
        $user = User::first();
        $this->actingAs($user);

        $p1 = Post::create(['title' => 'P1', 'body' => 'b', 'user_id' => $user->id]);
        $p2 = Post::create(['title' => 'P2', 'body' => 'b', 'user_id' => $user->id]);
        $p3 = Post::create(['title' => 'P3', 'body' => 'b', 'user_id' => $user->id]);

        $p1->delete();
        $p2->delete();

        $this->json('POST', '/luminix-api/posts/restore', ['ids' => [$p1->id, $p2->id]])
            ->assertStatus(204);

        // All three are now accessible
        $response = $this->json('GET', '/luminix-api/posts');
        $ids = array_column($response->json('data'), 'id');
        $this->assertContains($p1->id, $ids);
        $this->assertContains($p2->id, $ids);
        $this->assertContains($p3->id, $ids);
    }

    public function test_restore_many_requires_ids_field()
    {
        $user = User::first();
        $this->actingAs($user);

        $this->json('POST', '/luminix-api/posts/restore', [])->assertStatus(422);
    }

    public function test_restore_many_returns_404_when_no_records_match()
    {
        $user = User::first();
        $this->actingAs($user);

        $post = Post::create(['title' => 'Not deleted', 'body' => 'b', 'user_id' => $user->id]);

        // post is NOT soft-deleted, restoreMany only looks in trashed
        $this->json('POST', '/luminix-api/posts/restore', ['ids' => [$post->id]])
            ->assertStatus(404);
    }

    // ── Null / notNull filter on nullable column ─────────────────────────────

    public function test_null_filter_returns_records_with_null_field()
    {
        $user = User::first();
        $this->actingAs($user);

        Post::create(['title' => 'With notes',    'body' => 'b', 'notes' => 'some notes', 'user_id' => $user->id]);
        Post::create(['title' => 'Without notes', 'body' => 'b', 'notes' => null,          'user_id' => $user->id]);

        $response = $this->json('GET', '/luminix-api/posts', ['where' => ['notes:null' => 1]]);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Without notes', $response->json('data.0.title'));
    }

    public function test_not_null_filter_returns_records_with_non_null_field()
    {
        $user = User::first();
        $this->actingAs($user);

        Post::create(['title' => 'With notes',    'body' => 'b', 'notes' => 'some notes', 'user_id' => $user->id]);
        Post::create(['title' => 'Without notes', 'body' => 'b', 'notes' => null,          'user_id' => $user->id]);

        $response = $this->json('GET', '/luminix-api/posts', ['where' => ['notes:notNull' => 1]]);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('With notes', $response->json('data.0.title'));
    }
}
