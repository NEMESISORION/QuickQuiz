<?php

namespace Database\Factories;

use App\Models\AnswerOption;
use App\Models\LiveSessionParticipant;
use App\Models\LiveSessionResponse;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LiveSessionResponse> */
class LiveSessionResponseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'live_session_participant_id' => LiveSessionParticipant::factory(),
            'question_id' => Question::factory(),
            'answer_option_id' => AnswerOption::factory(),
            'is_correct' => false,
            'points_awarded' => 0,
            'answered_at' => now(),
        ];
    }
}
