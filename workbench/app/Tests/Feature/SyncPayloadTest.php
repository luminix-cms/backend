<?php

namespace Workbench\App\Tests\Feature;

use Workbench\App\Models\Category;
use Workbench\App\Models\ToDo;
use Workbench\App\Models\User;
use Workbench\App\Tests\TestCase;

/**
 * `sync` takes a list mixing plain ids and `{id, ...pivot}` objects, and
 * syncs every id in it whatever the order.
 */
class SyncPayloadTest extends TestCase
{
    protected ToDo $todo;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::where('email', 'user@example.com')->first();
        $this->actingAs($user);

        $this->todo = $user->toDos()->create(['title' => 'Mine', 'description' => 'desc']);
    }

    public function test_sync_keeps_a_plain_id_listed_between_objects()
    {
        $first = Category::create(['name' => 'First']);
        $next = Category::create(['name' => 'Next']);
        $plain = Category::create(['name' => 'Plain']);

        // `$next` takes the id right after `$first`, the key a plain id listed
        // after `{id: $first}` would get from a running counter.
        $this->assertSame($first->id + 1, $next->id);

        $this->json('POST', "/luminix-api/to-dos/{$this->todo->id}/categories/sync", [
            ['id' => $first->id],
            $plain->id,
            ['id' => $next->id, 'note' => 'next'],
        ])->assertStatus(200);

        $this->assertEqualsCanonicalizing(
            [$first->id, $next->id, $plain->id],
            $this->todo->categories()->pluck('categories.id')->all()
        );
        $this->assertDatabaseHas('category_to_do', [
            'to_do_id'    => $this->todo->id,
            'category_id' => $next->id,
            'note'        => 'next',
        ]);
    }

    public function test_sync_accepts_plain_ids_as_strings()
    {
        $first = Category::create(['name' => 'First']);
        $plain = Category::create(['name' => 'Plain']);

        $this->json('POST', "/luminix-api/to-dos/{$this->todo->id}/categories/sync", [
            ['id' => $first->id, 'note' => 'first'],
            (string) $plain->id,
        ])->assertStatus(200);

        $this->assertEqualsCanonicalizing(
            [$first->id, $plain->id],
            $this->todo->categories()->pluck('categories.id')->all()
        );
    }
}
