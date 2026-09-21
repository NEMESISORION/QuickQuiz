<?php

namespace App\Domain\LiveSessions;

use App\Models\LiveSession;
use App\Models\LiveSessionResponse;

class LiveSessionState
{
    public function version(LiveSession $liveSession): string
    {
        $participantCount = $liveSession->participants()->count();
        $responseCount = LiveSessionResponse::query()
            ->whereHas('participant', fn ($query) => $query->whereBelongsTo($liveSession))
            ->count();

        return implode(':', [
            $liveSession->status->value,
            $liveSession->current_question_id ?? 'none',
            $participantCount,
            $responseCount,
        ]);
    }
}
