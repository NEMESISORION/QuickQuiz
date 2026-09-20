<?php

namespace App\Models;

use App\Enums\QuestionType;
use Database\Factories\QuizAttemptQuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** @property QuestionType $type */
#[Fillable(['type', 'prompt', 'explanation', 'points', 'position'])]
class QuizAttemptQuestion extends Model
{
    /** @use HasFactory<QuizAttemptQuestionFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['type' => QuestionType::class, 'points' => 'integer', 'position' => 'integer'];
    }

    /** @return BelongsTo<QuizAttempt, $this> */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }

    /** @return BelongsTo<Question, $this> */
    public function sourceQuestion(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'source_question_id');
    }

    /** @return HasMany<QuizAttemptOption, $this> */
    public function options(): HasMany
    {
        return $this->hasMany(QuizAttemptOption::class)->orderBy('position')->orderBy('id');
    }
}
