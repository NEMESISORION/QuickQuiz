<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/** @property Carbon $answered_at */
#[Fillable(['answered_at'])]
class QuizAttemptAnswer extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['answered_at' => 'datetime'];
    }

    /** @return BelongsTo<QuizAttempt, $this> */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }

    /** @return BelongsTo<QuizAttemptQuestion, $this> */
    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizAttemptQuestion::class, 'quiz_attempt_question_id');
    }

    /** @return BelongsTo<QuizAttemptOption, $this> */
    public function option(): BelongsTo
    {
        return $this->belongsTo(QuizAttemptOption::class, 'quiz_attempt_option_id');
    }
}
