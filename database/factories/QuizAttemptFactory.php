<?php

namespace Database\Factories;

use App\Enums\QuizAttemptStatus;
use App\Enums\QuizReviewPolicy;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizAttempt>
 */
class QuizAttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory()->published(),
            'learner_id' => User::factory()->learner(),
            'status' => QuizAttemptStatus::InProgress,
            'attempt_number' => 1,
            'started_at' => now(),
            'expires_at' => now()->addMinutes(20),
            'submitted_at' => null,
            'duration_minutes_snapshot' => 20,
            'pass_percentage_snapshot' => 70,
            'review_policy_snapshot' => QuizReviewPolicy::Immediately,
            'review_available_at_snapshot' => null,
            'max_score' => 1,
            'score' => null,
            'passed' => null,
        ];
    }
}
