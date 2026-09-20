<?php

namespace Database\Factories;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'type' => QuestionType::MultipleChoice,
            'prompt' => fake()->sentence().'?',
            'explanation' => fake()->sentence(),
            'points' => 1,
            'position' => 1,
        ];
    }

    public function trueFalse(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => QuestionType::TrueFalse,
        ]);
    }
}
