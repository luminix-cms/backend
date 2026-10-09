<?php

namespace Workbench\App\Tests\Feature;

use Workbench\App\Models\User;
use Workbench\App\Tests\TestCase;

/**
 * `show` must answer identically for an id outside the caller's permission
 * and an id that does not exist at all. Checking the Gate against a row
 * fetched without the permission scope lets an unauthorized caller tell
 * existing ids apart from missing ones by comparing 401 against 404.
 */
class ShowAuthorizationTest extends TestCase
{
    public function test_show_responds_the_same_for_forbidden_and_missing_ids()
    {
        $owner = User::create([
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => bcrypt('password'),
        ]);

        $todo = $owner->toDos()->create([
            'title' => 'Not yours',
            'description' => 'desc',
        ]);

        $other = User::create([
            'name' => 'Other',
            'email' => 'other@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($other);

        $forbidden = $this->json('GET', "/luminix-api/to-dos/{$todo->id}");
        $missing = $this->json('GET', '/luminix-api/to-dos/999999');

        $forbidden->assertStatus(404);
        $missing->assertStatus(404);
    }
}
