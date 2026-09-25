<?php

namespace App\Domain\LiveSessions;

use App\Enums\LiveSessionStatus;
use App\Models\LiveSession;
use Illuminate\Support\Facades\DB;

class ExpireLiveSession
{
    public function handle(LiveSession $liveSession): LiveSession
    {
        return DB::transaction(function () use ($liveSession): LiveSession {
            $lockedSession = LiveSession::query()->lockForUpdate()->findOrFail($liveSession->getKey());

            if ($lockedSession->hasExpired()) {
                $lockedSession->update([
                    'status' => LiveSessionStatus::Completed,
                    'current_question_id' => null,
                    'current_question_started_at' => null,
                    'ended_at' => now(),
                ]);
            }

            return $lockedSession;
        });
    }
}
