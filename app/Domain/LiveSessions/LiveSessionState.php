<?php

namespace App\Domain\LiveSessions;

use App\Enums\LiveParticipantStatus;
use App\Models\LiveSession;
use App\Models\LiveSessionResponse;

class LiveSessionState
{
    public function version(LiveSession $liveSession): string
    {
        $participantCount = $liveSession->participants()->count();
        $onlineParticipantIds = $liveSession->participants()
            ->where('status', LiveParticipantStatus::Joined)
            ->where('last_seen_at', '>=', now()->subSeconds(15))
            ->orderBy('id')
            ->pluck('id')
            ->implode(',');
        $responseCount = LiveSessionResponse::query()
            ->whereHas('participant', fn ($query) => $query->whereBelongsTo($liveSession))
            ->count();

        return implode(':', [
            $liveSession->status->value,
            $liveSession->current_question_id ?? 'none',
            $participantCount,
            $onlineParticipantIds,
            $responseCount,
        ]);
    }
}
