<?php

namespace Luminix\Backend\Tests\Feature;

use Luminix\Backend\Tests\TestCase;

class RestApiTest extends TestCase
{
    public function test_application_is_up_and_running()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
