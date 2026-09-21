<?php

namespace Database\Factories;

use App\Enums\LiveSessionStatus;
use App\Models\LiveSession;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<LiveSession> */
class LiveSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory()->published(),
            'host_id' => User::factory()->educator(),
            'code' => Str::upper(fake()->unique()->bothify('??####')),
            'status' => LiveSessionStatus::Waiting,
            'current_question_id' => null,
            'current_question_started_at' => null,
            'started_at' => null,
            'ended_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => LiveSessionStatus::Active,
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => LiveSessionStatus::Completed,
            'started_at' => now()->subMinutes(10),
            'ended_at' => now(),
        ]);
    }
}
