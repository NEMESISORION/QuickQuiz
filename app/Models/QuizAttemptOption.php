<?php

namespace App\Models;

use Database\Factories\QuizAttemptOptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['content', 'is_correct', 'position'])]
class QuizAttemptOption extends Model
{
    /** @use HasFactory<QuizAttemptOptionFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_correct' => 'boolean', 'position' => 'integer'];
    }

    /** @return BelongsTo<QuizAttemptQuestion, $this> */
    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizAttemptQuestion::class, 'quiz_attempt_question_id');
    }

    /** @return BelongsTo<AnswerOption, $this> */
    public function sourceAnswerOption(): BelongsTo
    {
        return $this->belongsTo(AnswerOption::class, 'source_answer_option_id');
    }
}
