<?php

namespace Luminix\Backend\Tests\Feature;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Luminix\Backend\Tests\TestCase;
use Workbench\App\Models\User;

class RestApiTest extends TestCase
{
    public function test_apis_are_protected()
    {

        $this->json('GET', '/luminix-api/users')
            ->assertStatus(401);

        $this->json('GET', '/luminix-api/users/1')
            ->assertStatus(401);

        $this->json('POST', '/luminix-api/users')
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
    }

    public function test_app_is_up()
    {
        $this->get('/')
            ->assertStatus(200);

        $this->actingAs(User::find(1));

        $this->get('/')
            ->assertStatus(200);

        $this->json('GET', '/luminix-api/users')
            ->assertStatus(200);
        

    }
}

