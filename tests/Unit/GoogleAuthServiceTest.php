<?php

namespace Tests\Unit;

use App\Events\TokenRefreshed;
use App\Models\User;
use App\Services\GoogleAuthService;
use Google_Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class GoogleAuthServiceTest extends TestCase
{
    use RefreshDatabase;

    protected GoogleAuthService $service;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Event::fake();
        Log::shouldReceive('info', 'error', 'warning')->byDefault();

        $this->service = new GoogleAuthService;

        // Create and authenticate a test user
        $this->user = User::factory()->create([
            'google_id' => 'test_google_id',
            'google_access_token' => 'test_access_token',
            'google_refresh_token' => 'test_refresh_token',
            'google_token_expires_at' => now()->addHour(),
        ]);

        $this->actingAs($this->user);
    }

    public function test_create_client_throws_exception_if_credentials_missing(): void
    {
        $service = Mockery::mock(GoogleAuthService::class)->makePartial();
        $service->shouldReceive('credentialsExist')->andReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Google credentials file not found');

        $service->createClient('test');
    }

    public function test_load_token_returns_user_token_when_authenticated(): void
    {
        $token = $this->service->loadToken($this->user->google_id);

        $this->assertNotNull($token);
        $this->assertEquals('test_access_token', $token['access_token']);
        $this->assertEquals('test_refresh_token', $token['refresh_token']);
    }

    public function test_load_token_returns_null_when_user_not_authenticated(): void
    {
        auth()->logout();

        $token = $this->service->loadToken('test_account');

        $this->assertNull($token);
    }

    public function test_save_token_updates_authenticated_user(): void
    {
        $newToken = [
            'access_token' => 'new_access_token',
            'refresh_token' => 'new_refresh_token',
            'expires_in' => 3600,
        ];

        $this->service->saveToken($this->user->google_id, $newToken);

        $this->user->refresh();
        $this->assertEquals('new_access_token', $this->user->google_access_token);
        $this->assertEquals('new_refresh_token', $this->user->google_refresh_token);
        $this->assertNotNull($this->user->google_token_expires_at);
    }

    public function test_save_token_throws_exception_when_not_authenticated(): void
    {
        auth()->logout();

        $token = ['access_token' => 'test', 'refresh_token' => 'test', 'expires_in' => 3600];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User must be authenticated to save tokens');

        $this->service->saveToken('test_account', $token);
    }

    public function test_save_token_throws_exception_for_mismatched_account(): void
    {
        $token = ['access_token' => 'test', 'refresh_token' => 'test', 'expires_in' => 3600];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Account does not match authenticated user');

        $this->service->saveToken('different_account', $token);
    }

    public function test_revoke_token_clears_user_tokens(): void
    {
        $service = Mockery::mock(GoogleAuthService::class)->makePartial();

        $mockClient = Mockery::mock(Google_Client::class);
        $mockClient->shouldReceive('getAccessToken')->andReturn(['access_token' => 'test']);
        $mockClient->shouldReceive('revokeToken')->once();

        $service->shouldReceive('createClient')->andReturn($mockClient);

        $service->revokeToken($this->user->google_id);

        $this->user->refresh();
        $this->assertNull($this->user->google_access_token);
        $this->assertNull($this->user->google_refresh_token);
        $this->assertNull($this->user->google_token_expires_at);
    }

    public function test_revoke_token_throws_exception_when_not_authenticated(): void
    {
        auth()->logout();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User must be authenticated to revoke tokens');

        $this->service->revokeToken('test_account');
    }

    public function test_has_valid_token_returns_true_for_valid_user_token(): void
    {
        $service = Mockery::mock(GoogleAuthService::class)->makePartial();

        $mockClient = Mockery::mock(Google_Client::class);
        $mockClient->shouldReceive('setAccessToken')->once();
        $mockClient->shouldReceive('isAccessTokenExpired')->andReturn(false);

        $service->shouldReceive('createClient')->andReturn($mockClient);
        $service->shouldReceive('loadToken')->andReturn(['access_token' => 'valid_token']);

        $result = $service->hasValidToken($this->user->google_id);

        $this->assertTrue($result);
    }

    public function test_has_valid_token_returns_false_when_no_token(): void
    {
        auth()->logout();

        $result = $this->service->hasValidToken('test_account');

        $this->assertFalse($result);
    }

    public function test_refresh_token_updates_user_token(): void
    {
        $service = Mockery::mock(GoogleAuthService::class)->makePartial();

        $mockClient = Mockery::mock(Google_Client::class);
        $mockClient->shouldReceive('getRefreshToken')->andReturn('refresh_token');
        $mockClient->shouldReceive('fetchAccessTokenWithRefreshToken')->once();
        $mockClient->shouldReceive('getAccessToken')->andReturn([
            'access_token' => 'new_token',
            'refresh_token' => 'refresh_token',
            'expires_in' => 3600,
        ]);

        $service->shouldReceive('saveToken')->once();

        $service->refreshToken($mockClient, $this->user->google_id);

        Event::assertDispatched(TokenRefreshed::class);
    }

    public function test_create_client_uses_modern_oauth_prompt(): void
    {
        Storage::disk('local')->put('google/credentials.json', json_encode([
            'web' => [
                'client_id' => 'test_client_id',
                'client_secret' => 'test_client_secret',
            ],
        ]));

        $client = $this->service->createClient('test');

        $authConfig = $client->getConfig();
        $this->assertStringContains('select_account', $authConfig['prompt'] ?? '');
    }
}
