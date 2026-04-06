<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'google_id' => 'google_'.Str::random(24),
            'avatar' => fake()->imageUrl(128, 128, 'people'),
            'google_access_token' => null,
            'google_refresh_token' => null,
            'google_token_expires_at' => null,
            'email_verified_at' => now(),
        ];
    }
}
