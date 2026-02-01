<?php

namespace App\Http\Controllers;

use App\Services\GoogleAuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
  public function __construct(private GoogleAuthService $googleAuth) {}

  /**
   * Display OAuth authorization code for CLI authentication
   */
  public function token(Request $request)
  {
    $validated = $request->validate([
      'code' => 'required|string',
      'state' => 'sometimes|string',
    ]);

    // Extract account from state if present
    $account = null;
    if (isset($validated['state'])) {
      $state = json_decode($validated['state'], true);
      $account = $state['account'] ?? null;
    }

    // If account provided, save the token automatically
    if ($account) {
      try {
        $this->googleAuth->fetchAccessToken($validated['code'], $account);
        $message = "Token saved for account: {$account}";
      } catch (\Exception $e) {
        $message = "Error: {$e->getMessage()}";
      }
    } else {
      $message = null;
    }

    return view('token', [
      'code' => $validated['code'],
      'account' => $account,
      'message' => $message,
    ]);
  }

  /**
   * Start OAuth flow for an account
   */
  public function authorize(Request $request)
  {
    $validated = $request->validate([
      'account' => 'required|string|alpha_dash|max:50',
    ]);

    $authUrl = $this->googleAuth->getAuthorizationUrl($validated['account']);

    return redirect($authUrl);
  }

  /**
   * Check token status for an account
   */
  public function status(Request $request)
  {
    $validated = $request->validate([
      'account' => 'required|string|alpha_dash|max:50',
    ]);

    $account = $validated['account'];
    $hasToken = $this->googleAuth->hasValidToken($account);

    return response()->json([
      'account' => $account,
      'has_valid_token' => $hasToken,
    ]);
  }

  /**
   * Revoke token for an account
   */
  public function revoke(Request $request)
  {
    $validated = $request->validate([
      'account' => 'required|string|alpha_dash|max:50',
    ]);

    try {
      $this->googleAuth->revokeToken($validated['account']);

      return response()->json([
        'success' => true,
        'message' => "Token revoked for account: {$validated['account']}",
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage(),
      ], 500);
    }
  }
}
