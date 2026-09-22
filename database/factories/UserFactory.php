<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

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
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::Learner,
            'role_selected_at' => now(),
            'theme_preference' => 'system',
            'notifications_enabled' => true,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function educator(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => UserRole::Educator,
            'role_selected_at' => now(),
        ]);
    }

    public function learner(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => UserRole::Learner,
            'role_selected_at' => now(),
        ]);
    }

    public function withoutRole(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => null,
            'role_selected_at' => null,
        ]);
    }
}
