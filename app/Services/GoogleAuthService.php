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
use Illuminate\Support\Facades\Crypt;
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
     * Get credentials path for a specific account
     * Supports account-specific credentials files (e.g., credentials_aqcom.json)
     * Falls back to default credentials.json
     */
    protected function getCredentialsPath(?string $account = null): string
    {
        $disk = Storage::disk('local');

        if ($account) {
            // Try account-specific credentials first
            $accountSpecificPath = "google/credentials_{$account}.json";
            if ($disk->exists($accountSpecificPath)) {
                return $accountSpecificPath;
            }
        }

        // Fall back to default credentials
        return $this->defaultCredentialsPath;
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
            throw new InvalidCredentialsException($credentialsPath . ' (failed to read file)');
        }

        $credentials = json_decode($contents, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidCredentialsException($credentialsPath . ' (invalid JSON format: ' . json_last_error_msg() . ')');
        }

        if ($credentials === null) {
            throw new InvalidCredentialsException($credentialsPath . ' (JSON decode returned null)');
        }

        if (! isset($credentials['installed']) && ! isset($credentials['web'])) {
            throw new InvalidCredentialsException($credentialsPath . ' (missing OAuth client configuration)');
        }
    }

    /**
     * Create and configure a Google Client
     */
    public function createClient(?string $account = null): Google_Client
    {
        $credentialsPath = $this->getCredentialsPath($account);
        $this->validateCredentials($credentialsPath);

        $client = new Google_Client;
        $client->setApplicationName(config('app.name', 'Docket'));

        foreach ($this->scopes as $scope) {
            $client->addScope($scope);
        }

        // Load credentials from storage
        $credentialsJson = Storage::disk('local')->get($credentialsPath);
        if ($credentialsJson === null) {
            throw new InvalidCredentialsException($credentialsPath . ' (failed to read credentials file)');
        }

        $credentialsArray = json_decode($credentialsJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidCredentialsException($credentialsPath . ' (invalid JSON: ' . json_last_error_msg() . ')');
        }

        $client->setAuthConfig($credentialsArray);

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
                throw new \Exception('Failed to JSON encode state parameter: ' . json_last_error_msg());
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

                throw new \Exception('OAuth error: ' . $error);
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
        // Check cache first
        $cacheKey = "google_token_{$account}";
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $relativePath = "google/tokens/token_{$account}.json";

        if (! Storage::disk('local')->exists($relativePath)) {
            return null;
        }

        try {
            $encrypted = Storage::disk('local')->get($relativePath);
            if ($encrypted === null) {
                return null;
            }

            $decrypted = Crypt::decryptString($encrypted);
            $token = json_decode($decrypted, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Invalid token JSON format', [
                    'account' => $account,
                    'json_error' => json_last_error_msg()
                ]);
                return null;
            }

            if (! is_array($token)) {
                Log::error('Invalid token format', ['account' => $account]);
                return null;
            }

            // Cache for longer than refresh buffer to avoid cache misses during refresh
            $cacheResult = Cache::put($cacheKey, $token, $this->expiryBuffer * 2);
            if (! $cacheResult) {
                Log::warning('Failed to cache token', ['account' => $account]);
                // Don't fail - we can still return the token
            }

            return $token;
        } catch (\Exception $e) {
            Log::error('Failed to load token', [
                'account' => $account,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Save token for a specific account
     */
    public function saveToken(string $account, array $token): void
    {
        try {
            $relativePath = "google/tokens/token_{$account}.json";

            $jsonString = json_encode($token);
            if ($jsonString === false) {
                throw new \Exception('Failed to JSON encode token: ' . json_last_error_msg());
            }

            $encrypted = Crypt::encryptString($jsonString);

            $writeResult = Storage::disk('local')->put($relativePath, $encrypted);
            if (! $writeResult) {
                throw new \Exception('Storage::put() returned false - write operation failed');
            }

            // Update cache with longer TTL than refresh buffer
            $cacheKey = "google_token_{$account}";
            $cacheResult = Cache::put($cacheKey, $token, $this->expiryBuffer * 2);
            if (! $cacheResult) {
                Log::warning('Failed to cache token (file saved successfully)', ['account' => $account]);
                // Don't fail the entire operation for cache issues
            }

            Log::info('Token saved successfully', ['account' => $account]);
        } catch (\Exception $e) {
            Log::error('Failed to save token', [
                'account' => $account,
                'error' => $e->getMessage(),
            ]);

            throw $e;
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
     * Revoke token for an account
     */
    public function revokeToken(string $account): void
    {
        try {
            $client = $this->createClient($account);

            if ($client->getAccessToken()) {
                $client->revokeToken();
                Log::info('Token revoked via API', ['account' => $account]);
            }

            // Delete token file
            $relativePath = "google/tokens/token_{$account}.json";
            if (Storage::disk('local')->exists($relativePath)) {
                Storage::disk('local')->delete($relativePath);
            }

            // Clear cache
            $cacheKey = "google_token_{$account}";
            Cache::forget($cacheKey);

            Log::info('Token deleted successfully', ['account' => $account]);
        } catch (\Exception $e) {
            Log::error('Failed to revoke token', [
                'account' => $account,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
