<?php

namespace Database\Factories;

use App\Enums\LiveParticipantStatus;
use App\Models\LiveSession;
use App\Models\LiveSessionParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LiveSessionParticipant> */
class LiveSessionParticipantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'live_session_id' => LiveSession::factory(),
            'learner_id' => User::factory()->learner(),
            'status' => LiveParticipantStatus::Joined,
            'joined_at' => now(),
            'last_seen_at' => now(),
            'left_at' => null,
        ];
    }
}
