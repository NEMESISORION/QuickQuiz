<?php

namespace App\Domain\LiveSessions;

use App\Enums\LiveParticipantStatus;
use App\Enums\LiveSessionStatus;
use App\Models\LiveSession;
use App\Models\Question;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StartLiveSession
{
    public function handle(LiveSession $liveSession): LiveSession
    {
        return DB::transaction(function () use ($liveSession): LiveSession {
            $lockedSession = LiveSession::query()->lockForUpdate()->findOrFail($liveSession->getKey());

            if ($lockedSession->status !== LiveSessionStatus::Waiting) {
                throw ValidationException::withMessages(['session' => 'Only a waiting lobby can be started.']);
            }

            if (! $lockedSession->participants()->where('status', LiveParticipantStatus::Joined)->exists()) {
                throw ValidationException::withMessages(['session' => 'At least one learner must join before the session starts.']);
            }

            $firstQuestion = $lockedSession->quiz->questions()->first();

            if (! $firstQuestion instanceof Question) {
                throw ValidationException::withMessages(['session' => 'This quiz has no questions to present.']);
            }

            $lockedSession->update([
                'status' => LiveSessionStatus::Active,
                'current_question_id' => $firstQuestion->getKey(),
                'current_question_started_at' => now(),
                'started_at' => now(),
            ]);

            return $lockedSession->refresh();
        });
    }
}
