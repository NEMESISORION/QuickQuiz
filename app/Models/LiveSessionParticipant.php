<?php

namespace App\Models;

use App\Enums\LiveParticipantStatus;
use Database\Factories\LiveSessionParticipantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property LiveParticipantStatus $status
 * @property Carbon $joined_at
 * @property Carbon $last_seen_at
 * @property Carbon|null $left_at
 */
#[Fillable(['learner_id', 'status', 'joined_at', 'last_seen_at', 'left_at'])]
class LiveSessionParticipant extends Model
{
    /** @use HasFactory<LiveSessionParticipantFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => LiveParticipantStatus::class,
            'joined_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<LiveSession, $this> */
    public function liveSession(): BelongsTo
    {
        return $this->belongsTo(LiveSession::class);
    }

    /** @return BelongsTo<User, $this> */
    public function learner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'learner_id');
    }
}
