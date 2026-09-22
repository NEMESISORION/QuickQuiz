<?php

namespace App\Models;

use App\Enums\QuizReviewPolicy;
use App\Enums\QuizStatus;
use Database\Factories\QuizFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property QuizStatus $status
 * @property QuizReviewPolicy $review_policy
 * @property Carbon|null $opens_at
 * @property Carbon|null $closes_at
 * @property Carbon|null $published_at
 * @property Carbon|null $closed_at
 */
#[Fillable([
    'title',
    'description',
    'status',
    'duration_minutes',
    'pass_percentage',
    'max_attempts',
    'shuffle_questions',
    'shuffle_answers',
    'review_policy',
    'certificates_enabled',
    'opens_at',
    'closes_at',
    'published_at',
    'closed_at',
])]
class Quiz extends Model
{
    /** @use HasFactory<QuizFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => QuizStatus::class,
            'duration_minutes' => 'integer',
            'pass_percentage' => 'integer',
            'max_attempts' => 'integer',
            'shuffle_questions' => 'boolean',
            'shuffle_answers' => 'boolean',
            'review_policy' => QuizReviewPolicy::class,
            'certificates_enabled' => 'boolean',
            'opens_at' => 'datetime',
            'closes_at' => 'datetime',
            'published_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function educator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'educator_id');
    }

    /**
     * @return HasMany<Question, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('position')->orderBy('id');
    }

    /** @return HasMany<QuizAttempt, $this> */
    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /** @return HasMany<LiveSession, $this> */
    public function liveSessions(): HasMany
    {
        return $this->hasMany(LiveSession::class);
    }
}
