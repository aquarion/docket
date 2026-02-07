<?php

namespace App\Services;

use App\Events\AuthenticationFailed;
use App\Events\TokenExpired;
use App\Events\TokenRefreshed;
use App\Exceptions\InvalidCredentialsException;
use App\Exceptions\TokenNotFoundException;
use Google_Client;
use Google_Service_Calendar;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GoogleAuthService
{
    protected string $defaultCredentialsPath;

    protected string $redirectUri;

    protected array $scopes;

    protected int $expiryBuffer = 300; // 5 minutes

    public function __construct()
    {
        $this->defaultCredentialsPath = config('services.google.credentials_path', 'google/credentials.json');
        $this->redirectUri = config('services.google.redirect_uri', url('/token'));
        $this->scopes = config('services.google.scopes', []);
    }

    /**
     * Get credentials path shared by all accounts
     */
    protected function getCredentialsPath(?string $account = null): string
    {
        return $this->defaultCredentialsPath;
    }

    /**
     * Determine if Google Application Default Credentials should be used
     */
    protected function shouldUseApplicationDefaultCredentials(): bool
    {
        $configured = config('services.google.use_application_default_credentials');

        if (is_bool($configured)) {
            return $configured;
        }

        return $this->isGoogleCloudEnvironment();
    }

    /**
     * Detect if running on Google Cloud
     */
    protected function isGoogleCloudEnvironment(): bool
    {
        $cacheKey = 'gcp:environment';
        $cached = Cache::get($cacheKey);

        if (is_bool($cached)) {
            return $cached;
        }

        if ($this->hasGoogleCloudEnvironmentVariables()) {
            Cache::put($cacheKey, true, 60 * 60);

            return true;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Metadata-Flavor: Google\r\n",
                'timeout' => 0.2,
            ],
        ]);

        $response = @file_get_contents(
            'http://169.254.169.254/computeMetadata/v1/project/project-id',
            false,
            $context
        );

        $isGoogleCloud = $response !== false && $response !== '';
        Cache::put($cacheKey, $isGoogleCloud, 60 * 60);

        return $isGoogleCloud;
    }

    /**
     * Check for environment variables commonly set on Google Cloud
     */
    protected function hasGoogleCloudEnvironmentVariables(): bool
    {
        $variables = [
            'GOOGLE_CLOUD_PROJECT',
            'GCLOUD_PROJECT',
            'GCP_PROJECT',
            'K_SERVICE',
            'GAE_ENV',
            'GOOGLE_APPLICATION_CREDENTIALS',
        ];

        foreach ($variables as $variable) {
            if (! empty($_SERVER[$variable]) || ! empty($_ENV[$variable])) {
                return true;
            }

            if (getenv($variable) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate credentials file exists and is properly formatted
     */
    protected function validateCredentials(string $credentialsPath): void
    {
        $disk = Storage::disk('local');

        if (! $disk->exists($credentialsPath)) {
            throw new InvalidCredentialsException($credentialsPath);
        }

        // Validate credentials file is valid JSON with required fields
        $contents = $disk->get($credentialsPath);
        if ($contents === null) {
            throw new InvalidCredentialsException($credentialsPath.' (failed to read file)');
        }

        $credentials = json_decode($contents, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidCredentialsException($credentialsPath.' (invalid JSON format: '.json_last_error_msg().')');
        }

        if ($credentials === null) {
            throw new InvalidCredentialsException($credentialsPath.' (JSON decode returned null)');
        }

        if (! isset($credentials['installed']) && ! isset($credentials['web'])) {
            throw new InvalidCredentialsException($credentialsPath.' (missing OAuth client configuration)');
        }
    }

    /**
     * Create and configure a Google Client
     */
    public function createClient(?string $account = null): Google_Client
    {
        $credentialsPath = $this->getCredentialsPath($account);

        $client = new Google_Client;
        $client->setApplicationName(config('app.name', 'Docket'));

        foreach ($this->scopes as $scope) {
            $client->addScope($scope);
        }

        if (
            $this->shouldUseApplicationDefaultCredentials()
            && ! Storage::disk('local')->exists($credentialsPath)
        ) {
            $client->useApplicationDefaultCredentials();
        } else {
            $this->validateCredentials($credentialsPath);

            // Load credentials from storage
            $credentialsJson = Storage::disk('local')->get($credentialsPath);
            if ($credentialsJson === null) {
                throw new InvalidCredentialsException($credentialsPath.' (failed to read credentials file)');
            }

            $credentialsArray = json_decode($credentialsJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidCredentialsException($credentialsPath.' (invalid JSON: '.json_last_error_msg().')');
            }

            $client->setAuthConfig($credentialsArray);
        }

        // Essential settings for refresh tokens
        $client->setAccessType('offline');
        $client->setPrompt('consent'); // Force consent screen to ensure refresh token
        $client->setRedirectUri($this->redirectUri);

        // Load existing token if account is provided
        if ($account) {
            $token = $this->loadToken($account);
            if ($token) {
                $client->setAccessToken($token);

                // Refresh if expired or expires soon
                if ($this->shouldRefreshToken($client)) {
                    $this->refreshToken($client, $account);
                }
            }
        }

        return $client;
    }

    /**
     * Generate authorization URL for OAuth flow
     */
    public function getAuthorizationUrl(?string $account = null): string
    {
        $client = $this->createClient($account);

        // Add account to state parameter for callback
        if ($account) {
            $stateJson = json_encode(['account' => $account]);
            if ($stateJson === false) {
                throw new \Exception('Failed to JSON encode state parameter: '.json_last_error_msg());
            }
            $client->setState($stateJson);
        }

        return $client->createAuthUrl();
    }

    /**
     * Exchange authorization code for access token
     */
    public function fetchAccessToken(string $code, ?string $account = null): array
    {
        try {
            $client = $this->createClient($account);
            $accessToken = $client->fetchAccessTokenWithAuthCode($code);

            if (array_key_exists('error', $accessToken)) {
                $error = implode(', ', $accessToken);
                Log::error('OAuth token fetch failed', [
                    'account' => $account,
                    'error' => $error,
                ]);

                if ($account) {
                    event(new AuthenticationFailed($account, $error));
                }

                throw new \Exception('OAuth error: '.$error);
            }

            // Save token if account provided
            if ($account) {
                // Warn if no refresh token (shouldn't happen with offline access + force approval)
                if (! isset($accessToken['refresh_token'])) {
                    Log::warning('No refresh token received - token may expire permanently', [
                        'account' => $account,
                    ]);
                }

                $this->saveToken($account, $accessToken);
                Log::info('OAuth token saved', [
                    'account' => $account,
                    'has_refresh_token' => isset($accessToken['refresh_token']),
                ]);
            }

            return $accessToken;
        } catch (\Exception $e) {
            Log::error('OAuth token exchange failed', [
                'account' => $account,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Load token for a specific account
     */
    public function loadToken(string $account): ?array
    {
        // Only use authenticated user tokens - no more file-based tokens
        return $this->loadUserToken($account);
    }

    /**
     * Load token from authenticated user
     */
    protected function loadUserToken(string $account): ?array
    {
        if (! auth()->check()) {
            return null;
        }

        $user = auth()->user();

        // If no account specified or account matches user's google_id, use user's token
        if (($account === 'default' && $user->google_access_token) ||
            $account === $user->google_id ||
            $account === $user->email
        ) {

            if (! $user->google_access_token) {
                return null;
            }

            return [
                'access_token' => $user->google_access_token,
                'refresh_token' => $user->google_refresh_token,
                'expires_in' => $user->google_token_expires_at ?
                    $user->google_token_expires_at->timestamp - time() : null,
                'created' => $user->updated_at->timestamp ?? time(),
            ];
        }

        return null;
    }

    /**
     * Save token for authenticated user only
     */
    public function saveToken(string $account, array $token): void
    {
        if (! auth()->check()) {
            throw new \Exception('User must be authenticated to save tokens');
        }

        $user = auth()->user();

        // Only save for matching accounts
        if (($account === 'default' && $user->google_access_token !== null) ||
            $account === $user->google_id ||
            $account === $user->email
        ) {
            $expiresIn = $token['expires_in'] ?? null;
            $user->update([
                'google_access_token' => $token['access_token'] ?? null,
                'google_refresh_token' => $token['refresh_token'] ?? $user->google_refresh_token,
                'google_token_expires_at' => $expiresIn ? now()->addSeconds($expiresIn) : null,
            ]);

            Log::info('Token saved to user record', ['account' => $account, 'user_id' => $user->id]);
        } else {
            throw new \Exception('Account does not match authenticated user');
        }
    }

    /**
     * Refresh an expired token
     */
    public function refreshToken(Google_Client $client, string $account): void
    {
        try {
            $refreshToken = $client->getRefreshToken();

            if (! $refreshToken) {
                Log::error('No refresh token available', ['account' => $account]);
                event(new TokenExpired($account));

                throw new \Exception("No refresh token available for account: {$account}");
            }

            Log::info('Refreshing token', ['account' => $account]);

            $client->fetchAccessTokenWithRefreshToken($refreshToken);
            $newToken = $client->getAccessToken();

            // Preserve the refresh token - Google doesn't always return it in the new token
            if (! isset($newToken['refresh_token']) && $refreshToken) {
                $newToken['refresh_token'] = $refreshToken;
            }

            // Force update the created timestamp to current time for proper expiry calculation
            // The Google Client preserves the original created timestamp which causes issues
            $newToken['created'] = time();

            $this->saveToken($account, $newToken);

            event(new TokenRefreshed($account, $newToken));

            Log::info('Token refreshed successfully', ['account' => $account]);
        } catch (\Exception $e) {
            Log::error('Token refresh failed', [
                'account' => $account,
                'error' => $e->getMessage(),
            ]);

            event(new TokenExpired($account));

            throw $e;
        }
    }

    /**
     * Check if token should be refreshed (expired or expires soon)
     */
    protected function shouldRefreshToken(Google_Client $client): bool
    {
        if ($client->isAccessTokenExpired()) {
            return true;
        }

        $token = $client->getAccessToken();
        if (! isset($token['expires_in']) || ! isset($token['created'])) {
            // If we can't determine expiry, assume we should refresh
            Log::warning('Unable to determine token expiry, forcing refresh', ['token_keys' => array_keys($token)]);

            return true;
        }

        // Refresh if expires within buffer period (5 minutes)
        $expiresAt = $token['created'] + $token['expires_in'];
        $now = time();
        $timeUntilExpiry = $expiresAt - $now;

        return $timeUntilExpiry < $this->expiryBuffer;
    }

    /**
     * Check if an account has a valid token
     */
    public function hasValidToken(string $account): bool
    {
        $token = $this->loadToken($account);

        if (! $token) {
            return false;
        }

        $client = $this->createClient($account);
        $client->setAccessToken($token);

        return ! $client->isAccessTokenExpired();
    }

    /**
     * Create a Calendar service for an account
     */
    public function getCalendarService(string $account): Google_Service_Calendar
    {
        try {
            $client = $this->createClient($account);

            if (! $client->getAccessToken()) {
                Log::error('No access token available', ['account' => $account]);

                throw new TokenNotFoundException($account);
            }

            return new Google_Service_Calendar($client);
        } catch (\Exception $e) {
            Log::error('Failed to create calendar service', [
                'account' => $account,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Revoke token for authenticated user
     */
    public function revokeToken(string $account): void
    {
        if (! auth()->check()) {
            throw new \Exception('User must be authenticated to revoke tokens');
        }

        try {
            $client = $this->createClient($account);

            if ($client->getAccessToken()) {
                $client->revokeToken();
                Log::info('Token revoked via API', ['account' => $account]);
            }

            // Clear user token data
            $user = auth()->user();
            $user->update([
                'google_access_token' => null,
                'google_refresh_token' => null,
                'google_token_expires_at' => null,
            ]);

            Log::info('User token deleted successfully', ['account' => $account, 'user_id' => $user->id]);
        } catch (\Exception $e) {
            Log::error('Failed to revoke token', [
                'account' => $account,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
