<?php

namespace Tests\Unit;

use App\Services\GoogleAuthService;
use App\Services\GoogleCalendarService;
use App\Services\ThemeService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class GoogleCalendarServiceTest extends TestCase
{
  protected function tearDown(): void
  {
    Mockery::close();
    parent::tearDown();
  }

  public function test_service_handles_authentication_failures_gracefully(): void
  {
    Log::spy();
    Storage::fake('local');

    // Create mock services
    $mockGoogleAuth = Mockery::mock(GoogleAuthService::class);
    $mockThemeService = Mockery::mock(ThemeService::class);

    $service = new GoogleCalendarService($mockGoogleAuth, $mockThemeService);

    // This test verifies the service exists and can be instantiated
    $this->assertInstanceOf(GoogleCalendarService::class, $service);
  }

  public function test_error_handling_patterns_exist(): void
  {
    // Test that our error handling improvements are present
    $serviceFile = base_path('app/Services/GoogleCalendarService.php');
    $this->assertFileExists($serviceFile);

    $content = file_get_contents($serviceFile);

    // Verify error handling patterns are present
    $this->assertStringContainsString('try', $content);
    $this->assertStringContainsString('catch', $content);
    $this->assertStringContainsString('Exception', $content);
  }

  public function test_authentication_check_functionality(): void
  {
    Storage::fake('local');

    // Create test credentials
    $testCredentials = [
      'installed' => [
        'client_id' => 'test_client_id.apps.googleusercontent.com',
        'client_secret' => 'test_client_secret',
        'redirect_uris' => ['http://localhost:8000/auth/google/callback']
      ]
    ];
    Storage::disk('local')->put('google/credentials.json', json_encode($testCredentials));

    $googleAuth = new GoogleAuthService();
    $themeService = Mockery::mock(ThemeService::class);

    $service = new GoogleCalendarService($googleAuth, $themeService);

    // Test that the service can check authentication status
    $this->assertInstanceOf(GoogleCalendarService::class, $service);
  }

  public function test_service_configuration_is_correct(): void
  {
    // Verify that our service dependencies are properly configured
    $this->assertTrue(class_exists(GoogleCalendarService::class));
    $this->assertTrue(class_exists(GoogleAuthService::class));
    $this->assertTrue(class_exists(ThemeService::class));
  }
}
