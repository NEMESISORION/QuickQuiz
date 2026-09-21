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
 * @property Carbon|null $started_at
 * @property Carbon|null $ended_at
 */
#[Fillable(['host_id', 'code', 'status', 'started_at', 'ended_at'])]
class LiveSession extends Model
{
    /** @use HasFactory<LiveSessionFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => LiveSessionStatus::class,
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
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

    /** @return HasMany<LiveSessionParticipant, $this> */
    public function participants(): HasMany
    {
        return $this->hasMany(LiveSessionParticipant::class)->oldest('joined_at');
    }
}
