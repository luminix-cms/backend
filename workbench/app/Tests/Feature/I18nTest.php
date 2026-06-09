<?php

namespace Workbench\App\Tests\Feature;

use Workbench\App\Models\User;
use Workbench\App\Tests\TestCase;

class I18nTest extends TestCase
{
    public function test_unauthorized_response_carries_english_translation(): void
    {
        // Create a second user so we have two distinct users in the DB.
        $john = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
        ]);

        // Acting as John, try to read the seeded user (id=1).
        // The gate `read-user` only allows a user to read their own record,
        // so this triggers abort(401, __('luminix-backend::backend.unauthorized')).
        $this->actingAs($john)
            ->json('GET', '/luminix-api/users/1')
            ->assertStatus(401)
            ->assertJson(['message' => 'You are not authorized to perform this action.']);
    }
}
