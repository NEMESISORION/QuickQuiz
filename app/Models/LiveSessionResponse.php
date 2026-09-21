<?php

namespace App\Models;

use Database\Factories\LiveSessionResponseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property bool $is_correct
 * @property int $points_awarded
 * @property Carbon $answered_at
 */
#[Fillable(['question_id', 'answer_option_id', 'is_correct', 'points_awarded', 'answered_at'])]
class LiveSessionResponse extends Model
{
    /** @use HasFactory<LiveSessionResponseFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'points_awarded' => 'integer',
            'answered_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<LiveSessionParticipant, $this> */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(LiveSessionParticipant::class, 'live_session_participant_id');
    }

    /** @return BelongsTo<Question, $this> */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /** @return BelongsTo<AnswerOption, $this> */
    public function answerOption(): BelongsTo
    {
        return $this->belongsTo(AnswerOption::class);
    }
}
