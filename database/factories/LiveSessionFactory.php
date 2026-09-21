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
            'started_at' => null,
            'ended_at' => null,
        ];
    }
}
