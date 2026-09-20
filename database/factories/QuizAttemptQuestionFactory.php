<?php

namespace Database\Factories;

use App\Enums\QuestionType;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizAttemptQuestion>
 */
class QuizAttemptQuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_attempt_id' => QuizAttempt::factory(),
            'source_question_id' => null,
            'type' => QuestionType::MultipleChoice,
            'prompt' => fake()->sentence().'?',
            'explanation' => fake()->sentence(),
            'points' => 1,
            'position' => 1,
        ];
    }
}
