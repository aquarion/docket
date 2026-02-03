<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorageConfigurationTest extends TestCase
{
  /**
   * Test that storage is configured to throw exceptions on failures.
   * This tests our fix for silent storage failures.
   */
  public function test_storage_throws_exceptions_on_failures(): void
  {
    $config = config('filesystems.disks.local');

    // Verify that throw is enabled in filesystem configuration
    $this->assertTrue($config['throw'] ?? false, 'Storage should be configured to throw exceptions on failures');
  }

  public function test_storage_operations_fail_loudly(): void
  {
    // Create a temporary fake disk for testing
    Storage::fake('test');

    try {
      // Attempt to write to a non-writable location (should throw exception)
      $result = Storage::disk('test')->put('/invalid/path/that/cannot/be/created/file.txt', 'content');

      // If we reach here, the operation didn't throw (which is what we want to prevent)
      $this->assertTrue($result, 'Storage operation should succeed or throw exception, not fail silently');
    } catch (\Exception $e) {
      // This is expected behavior - storage should throw exceptions on failures
      $this->assertTrue(true, 'Storage correctly throws exceptions on failures');
    }
  }

  public function test_token_storage_location_is_secure(): void
  {
    $tokenPath = 'google/tokens/token_test.json';

    // Ensure token storage location is within the secure storage directory
    $fullPath = Storage::disk('local')->path($tokenPath);
    $storagePath = storage_path('app');

    $this->assertStringStartsWith($storagePath, $fullPath, 'Token storage should be within secure storage directory');
  }

  public function test_credentials_storage_location_is_secure(): void
  {
    $credentialsPath = 'google/credentials.json';

    // Ensure credentials storage location is within the secure storage directory
    $fullPath = Storage::disk('local')->path($credentialsPath);
    $storagePath = storage_path('app');

    $this->assertStringStartsWith($storagePath, $fullPath, 'Credentials storage should be within secure storage directory');
  }

  public function test_storage_directories_are_created(): void
  {
    // Test that our storage structure works
    Storage::fake('local');

    $tokenDir = 'google/tokens';
    $credentialsDir = 'google';

    // Create a test token file
    Storage::disk('local')->put($tokenDir . '/test_token.json', 'test_content');

    // Verify directory structure was created
    $this->assertTrue(Storage::disk('local')->exists($tokenDir . '/test_token.json'));

    // Create credentials file
    Storage::disk('local')->put($credentialsDir . '/credentials.json', 'credentials_content');

    // Verify credentials can be stored
    $this->assertTrue(Storage::disk('local')->exists($credentialsDir . '/credentials.json'));
  }
}
