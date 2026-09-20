<?php

namespace Database\Factories;

use App\Models\QuizAttemptOption;
use App\Models\QuizAttemptQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizAttemptOption>
 */
class QuizAttemptOptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_attempt_question_id' => QuizAttemptQuestion::factory(),
            'source_answer_option_id' => null,
            'content' => fake()->words(3, true),
            'is_correct' => false,
            'position' => 1,
        ];
    }
}
