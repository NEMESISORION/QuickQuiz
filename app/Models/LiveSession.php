<?php

namespace App\Models;

use App\Enums\LiveSessionStatus;
use Database\Factories\LiveSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property LiveSessionStatus $status
 * @property Carbon|null $current_question_started_at
 * @property Carbon|null $started_at
 * @property Carbon|null $ended_at
 */
#[Fillable(['host_id', 'code', 'status', 'current_question_id', 'current_question_started_at', 'started_at', 'ended_at'])]
class LiveSession extends Model
{
    /** @use HasFactory<LiveSessionFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => LiveSessionStatus::class,
            'current_question_started_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function expiresAt(): ?Carbon
    {
        if ($this->started_at === null || $this->quiz->duration_minutes === null) {
            return null;
        }

        return $this->started_at->copy()->addMinutes($this->quiz->duration_minutes);
    }

    public function hasExpired(): bool
    {
        return $this->status === LiveSessionStatus::Active && $this->expiresAt()?->lessThanOrEqualTo(now()) === true;
    }

    /** @return BelongsTo<Quiz, $this> */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /** @return BelongsTo<User, $this> */
    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    /** @return BelongsTo<Question, $this> */
    public function currentQuestion(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'current_question_id');
    }

    /** @return HasMany<LiveSessionParticipant, $this> */
    public function participants(): HasMany
    {
        return $this->hasMany(LiveSessionParticipant::class)->oldest('joined_at');
    }
}
