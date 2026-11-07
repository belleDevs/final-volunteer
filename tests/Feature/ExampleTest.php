<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    public function test_forwarded_https_requests_generate_secure_asset_urls(): void
    {
        $response = $this->get('/login', [
            'X-Forwarded-Proto' => 'https',
        ]);

        $response->assertOk();
        $response->assertSee('https://127.0.0.1:8000/build/assets/app-', false);
    }
}
