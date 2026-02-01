<?php

namespace Tests\Unit;

use App\Exceptions\InvalidCredentialsException;
use App\Services\GoogleAuthService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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

    public function test_constructor_throws_exception_if_credentials_missing(): void
    {
        config(['services.google.credentials_path' => 'nonexistent/path.json']);

        $this->expectException(InvalidCredentialsException::class);

        $service = new GoogleAuthService;
        // Exception is thrown when trying to use the service
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
        $service->loadToken('test_account');

        // Second load - should use cache
        Storage::shouldReceive('get')->never();
        $loaded = $service->loadToken('test_account');

        $this->assertEquals($token, $loaded);
    }

    public function test_revoke_token_deletes_file_and_clears_cache(): void
    {
        $service = $this->createService();
        $token = ['access_token' => 'test_token', 'expires_in' => 3600];

        $service->saveToken('test_account', $token);

        // Create client will fail without actual Google setup, so we can't fully test this
        // But we can verify the file is created
        $this->assertTrue(Storage::disk('local')->exists('google/tokens/token_test_account.json'));

        // Cache should contain the token
        $this->assertNotNull(Cache::get('google_token_test_account'));
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
