<?php

namespace App\Domain\LiveSessions;

use App\Enums\LiveSessionStatus;
use App\Models\LiveSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdvanceLiveSession
{
    public function handle(LiveSession $liveSession): LiveSession
    {
        return DB::transaction(function () use ($liveSession): LiveSession {
            $lockedSession = LiveSession::query()->lockForUpdate()->findOrFail($liveSession->getKey());

            if ($lockedSession->status !== LiveSessionStatus::Active || $lockedSession->current_question_id === null) {
                throw ValidationException::withMessages(['session' => 'Only an active question can be advanced.']);
            }

            $questionIds = $lockedSession->quiz->questions()->pluck('id')->all();
            $currentIndex = array_search($lockedSession->current_question_id, $questionIds, true);
            $nextQuestionId = $currentIndex === false ? null : ($questionIds[$currentIndex + 1] ?? null);

            if ($nextQuestionId === null) {
                $lockedSession->update([
                    'status' => LiveSessionStatus::Completed,
                    'current_question_id' => null,
                    'current_question_started_at' => null,
                    'ended_at' => now(),
                ]);
            } else {
                $lockedSession->update([
                    'current_question_id' => $nextQuestionId,
                    'current_question_started_at' => now(),
                ]);
            }

            return $lockedSession->refresh();
        });
    }
}
