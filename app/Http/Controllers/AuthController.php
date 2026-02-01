<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
  /**
   * Display OAuth authorization code for CLI authentication
   */
  public function token(Request $request)
  {
    $validated = $request->validate([
      'code' => 'required|string',
    ]);

    return view('token', [
      'code' => $validated['code'],
    ]);
  }
}
