<?php

namespace Tests\Unit;

use App\Exceptions\InvalidCredentialsException;
use App\Services\GoogleAuthService;
use Google_Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class GoogleAuthServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Event::fake();
        Log::spy();
        Cache::flush();
    }

    public function test_create_client_throws_exception_if_credentials_missing(): void
    {
        config(['services.google.credentials_path' => 'nonexistent/path.json']);

        $this->expectException(InvalidCredentialsException::class);

        $service = new GoogleAuthService;
        // Exception is thrown when validateCredentials() is called by createClient()
        $service->createClient();
    }

    public function test_load_token_returns_null_if_file_not_exists(): void
    {
        $service = $this->createService();

        $result = $service->loadToken('nonexistent');

        $this->assertNull($result);
    }

    public function test_save_and_load_token_with_encryption(): void
    {
        $service = $this->createService();
        $token = ['access_token' => 'test_token', 'expires_in' => 3600];

        $service->saveToken('test_account', $token);

        $loaded = $service->loadToken('test_account');

        $this->assertEquals($token, $loaded);
    }

    public function test_load_token_uses_cache(): void
    {
        $service = $this->createService();
        $token = ['access_token' => 'test_token', 'expires_in' => 3600];

        $service->saveToken('test_account', $token);

        // First load - from storage
        $loaded1 = $service->loadToken('test_account');
        $this->assertEquals($token, $loaded1);

        // Second load - should use cache, not storage
        $loaded2 = $service->loadToken('test_account');
        $this->assertEquals($token, $loaded2);

        // Verify cache contains the token
        $cached = Cache::get('google_token_test_account');
        $this->assertEquals($token, $cached);
    }

    public function test_revoke_token_deletes_file_and_clears_cache(): void
    {
        // Create a partial mock to avoid actually creating Google_Client
        $service = Mockery::mock(GoogleAuthService::class)->makePartial();

        // Mock the createClient method to return a mock Google_Client
        $mockClient = Mockery::mock(Google_Client::class);
        $mockClient->shouldReceive('getAccessToken')
            ->once()
            ->andReturn(['access_token' => 'test_token']);
        $mockClient->shouldReceive('revokeToken')
            ->once();

        $service->shouldReceive('createClient')
            ->with('test_account')
            ->once()
            ->andReturn($mockClient);

        // Set up initial token
        $token = ['access_token' => 'test_token', 'expires_in' => 3600];
        Storage::disk('local')->put('google/tokens/token_test_account.json', 'encrypted_content');
        Cache::put('google_token_test_account', $token, 300);

        // Verify initial state
        $this->assertTrue(Storage::disk('local')->exists('google/tokens/token_test_account.json'));
        $this->assertNotNull(Cache::get('google_token_test_account'));

        // Call revokeToken
        $service->revokeToken('test_account');

        // Verify token file was deleted
        $this->assertFalse(Storage::disk('local')->exists('google/tokens/token_test_account.json'));

        // Verify cache was cleared
        $this->assertNull(Cache::get('google_token_test_account'));

        // Verify log was called
        Log::shouldHaveReceived('info')
            ->with('Token deleted successfully', ['account' => 'test_account']);
    }

    public function test_token_saved_event_logged(): void
    {
        $service = $this->createService();
        $token = ['access_token' => 'test_token', 'expires_in' => 3600];

        $service->saveToken('test_account', $token);

        Log::shouldHaveReceived('info')
            ->with('Token saved successfully', ['account' => 'test_account']);
    }

    protected function createService(): GoogleAuthService
    {
        // Create a dummy credentials file for testing in storage
        $disk = Storage::disk('local');
        $credentialsPath = 'google/credentials.json';

        if (! $disk->exists($credentialsPath)) {
            $disk->put($credentialsPath, json_encode(['installed' => ['client_id' => 'test']]));
        }

        return new GoogleAuthService;
    }
}
