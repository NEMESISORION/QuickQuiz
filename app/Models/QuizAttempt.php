<?php

namespace App\Models;

use App\Enums\QuizAttemptStatus;
use App\Enums\QuizReviewPolicy;
use Database\Factories\QuizAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property QuizAttemptStatus $status
 * @property QuizReviewPolicy $review_policy_snapshot
 * @property Carbon $started_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $submitted_at
 * @property Carbon|null $review_available_at_snapshot
 */
#[Fillable([
    'status', 'attempt_number', 'started_at', 'expires_at', 'submitted_at',
    'duration_minutes_snapshot', 'pass_percentage_snapshot', 'review_policy_snapshot',
    'review_available_at_snapshot',
    'max_score', 'score', 'passed',
])]
class QuizAttempt extends Model
{
    /** @use HasFactory<QuizAttemptFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => QuizAttemptStatus::class,
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'submitted_at' => 'datetime',
            'duration_minutes_snapshot' => 'integer',
            'pass_percentage_snapshot' => 'integer',
            'review_policy_snapshot' => QuizReviewPolicy::class,
            'review_available_at_snapshot' => 'datetime',
            'max_score' => 'integer',
            'score' => 'integer',
            'passed' => 'boolean',
        ];
    }

    /** @return BelongsTo<Quiz, $this> */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /** @return BelongsTo<User, $this> */
    public function learner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'learner_id');
    }

    /** @return HasMany<QuizAttemptQuestion, $this> */
    public function questions(): HasMany
    {
        return $this->hasMany(QuizAttemptQuestion::class)->orderBy('position')->orderBy('id');
    }

    /** @return HasMany<QuizAttemptAnswer, $this> */
    public function answers(): HasMany
    {
        return $this->hasMany(QuizAttemptAnswer::class);
    }
}
