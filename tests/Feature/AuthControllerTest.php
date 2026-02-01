<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_token_endpoint_requires_code_parameter(): void
    {
        $response = $this->get('/token');

        $response->assertStatus(302); // Redirects due to validation failure
    }

    public function test_token_endpoint_displays_code(): void
    {
        $response = $this->get('/token?code=test_auth_code');

        $response->assertStatus(200);
        $response->assertViewIs('token');
        $response->assertViewHas('code', 'test_auth_code');
    }

    public function test_status_endpoint_requires_account_parameter(): void
    {
        $response = $this->get('/auth/google/status');

        $response->assertStatus(302); // Redirects due to validation failure
    }

    public function test_status_endpoint_returns_json(): void
    {
        $response = $this->get('/auth/google/status?account=test');

        $response->assertStatus(200);
        $response->assertJson([
            'account' => 'test',
            'has_valid_token' => false,
        ]);
    }

    public function test_authorize_requires_account_parameter(): void
    {
        $response = $this->get('/auth/google/authorize');

        $response->assertStatus(302); // Redirects due to validation failure
    }

    public function test_authorize_validates_account_format(): void
    {
        $response = $this->get('/auth/google/authorize?account=invalid@email');

        $response->assertStatus(302); // Validation failure - invalid characters
    }

    public function test_revoke_requires_account_parameter(): void
    {
        $response = $this->delete('/auth/google/revoke');

        $response->assertStatus(302); // Redirects due to validation failure
    }

    public function test_rate_limiting_on_authorize(): void
    {
        // Make 11 requests (limit is 10 per minute)
        for ($i = 0; $i < 11; $i++) {
            $response = $this->get('/auth/google/authorize?account=test');
        }

        $response->assertStatus(429); // Too Many Requests
    }
}
