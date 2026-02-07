<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class LoginController extends Controller
{
    /**
     * Redirect to Google OAuth
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->scopes(config('services.google.oauth_scopes', []))
            ->redirect();
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            // Check if user already exists by Google ID
            $user = User::where('google_id', $googleUser->getId())->first();

            if ($user) {
                // Update existing user info
                $user->update([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'avatar' => $googleUser->getAvatar(),
                ]);
            } else {
                // Check if user exists by email (existing account)
                $user = User::where('email', $googleUser->getEmail())->first();

                if ($user) {
                    // Link existing user to Google account
                    $user->update([
                        'google_id' => $googleUser->getId(),
                        'avatar' => $googleUser->getAvatar(),
                    ]);
                } else {
                    // Create new user
                    $user = User::create([
                        'name' => $googleUser->getName(),
                        'email' => $googleUser->getEmail(),
                        'google_id' => $googleUser->getId(),
                        'avatar' => $googleUser->getAvatar(),
                        'email_verified_at' => now(), // Google emails are pre-verified
                    ]);
                }
            }

            // Store Google tokens for calendar access
            $this->updateUserTokens($user, $googleUser);

            // Log the user in
            Auth::login($user, true);

            return redirect()->intended(route('home'));
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Unable to login with Google. Please try again.');
        }
    }

    /**
     * Show the login form
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle user logout
     */
    public function logout(Request $request)
    {

        return redirect()->route('home');
    }

    /**
     * API endpoint to get authentication URL
     */
    public function getGoogleAuthUrl()
    {
        $authUrl = Socialite::driver('google')
            ->scopes(config('services.google.oauth_scopes', []))
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        return response()->json([
            'auth_url' => $authUrl,
        ]);
    }

    /**
     * API endpoint to handle Google OAuth token
     */
    public function handleGoogleToken(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Find or create user (same logic as web callback)
            $user = User::where('google_id', $googleUser->getId())->first();

            if (! $user) {
                $user = User::where('email', $googleUser->getEmail())->first();

                if ($user) {
                    $user->update([
                        'google_id' => $googleUser->getId(),
                        'avatar' => $googleUser->getAvatar(),
                    ]);
                } else {
                    $user = User::create([
                        'name' => $googleUser->getName(),
                        'email' => $googleUser->getEmail(),
                        'google_id' => $googleUser->getId(),
                        'avatar' => $googleUser->getAvatar(),
                        'email_verified_at' => now(),
                    ]);
                }
            }

            // Generate API token (if using Sanctum)
            $token = $user->createToken('api-token')->plainTextToken ?? Str::random(80);

            return response()->json([
                'success' => true,
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication failed',
            ], 401);
        }
    }

    /**
     * Update user's Google tokens
     */
    private function updateUserTokens(User $user, \Laravel\Socialite\Contracts\User $googleUser): void
    {
        // Get the token from Socialite
        $token = $googleUser->token;
        $refreshToken = $googleUser->refreshToken;
        $expiresIn = $googleUser->expiresIn;

        if ($token) {
            $user->update([
                'google_access_token' => $token,
                'google_refresh_token' => $refreshToken,
                'google_token_expires_at' => $expiresIn ? now()->addSeconds($expiresIn) : null,
            ]);
        }
    }
}
