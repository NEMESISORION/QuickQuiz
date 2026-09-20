<?php

namespace Database\Factories;

use App\Enums\QuizReviewPolicy;
use App\Enums\QuizStatus;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quiz>
 */
class QuizFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'educator_id' => User::factory()->educator(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => QuizStatus::Draft,
            'duration_minutes' => 20,
            'pass_percentage' => 70,
            'max_attempts' => 1,
            'shuffle_questions' => false,
            'shuffle_answers' => false,
            'review_policy' => QuizReviewPolicy::Immediately,
            'opens_at' => null,
            'closes_at' => null,
            'published_at' => null,
            'closed_at' => null,
        ];
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => QuizStatus::Scheduled,
            'opens_at' => now()->addDay(),
            'closes_at' => now()->addWeek(),
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => QuizStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => QuizStatus::Closed,
            'published_at' => now()->subDay(),
            'closed_at' => now(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => QuizStatus::Archived,
        ]);
    }
}
