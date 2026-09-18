<?php

namespace Tests\Feature;

use Tests\TestCase;

class ErrorResponseTest extends TestCase
{
    public function test_api_route_returns_json_with_a_404_when_the_route_is_missing(): void
    {
        $response = $this->get('/api/missing');

        $response
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonStructure(['message']);
    }
}
