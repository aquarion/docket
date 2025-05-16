<?php

use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Config;


Route::get('/', function () {
    return view('welcome');
});


Route::get('/auth/google/redirect', function () {
    return Socialite::driver('google')->redirect();
});


Route::get('/auth/google/callback', function () {
    $googleUser = Socialite::driver('google')
        ->scopes(Config::get('services.google.scopes'))
        ->user();

    $user = User::updateOrCreate([
        'id' => $googleUser->email,
    ], [
        'name' => $googleUser->name,
        'email' => $googleUser->email,
        'password' => str()->random(),
        'github_token' => $googleUser->token,
        'github_refresh_token' => $googleUser->refreshToken,
    ]);

    Auth::login($user);

    return redirect('/dashboard');
});
