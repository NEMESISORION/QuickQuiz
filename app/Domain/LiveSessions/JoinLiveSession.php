<?php

namespace App\Domain\LiveSessions;

use App\Enums\LiveParticipantStatus;
use App\Models\LiveSession;
use App\Models\LiveSessionParticipant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JoinLiveSession
{
    public function handle(string $code, User $learner): LiveSessionParticipant
    {
        return DB::transaction(function () use ($code, $learner): LiveSessionParticipant {
            $liveSession = LiveSession::query()
                ->where('code', $code)
                ->lockForUpdate()
                ->first();

            if (! $liveSession instanceof LiveSession || ! $liveSession->status->acceptsParticipants()) {
                throw ValidationException::withMessages([
                    'code' => 'This live session code is invalid or no longer accepting learners.',
                ]);
            }

            $participant = $liveSession->participants()->firstOrNew([
                'learner_id' => $learner->getKey(),
            ]);

            if (! $participant->exists) {
                $participant->joined_at = now();
            }

            $participant->status = LiveParticipantStatus::Joined;
            $participant->last_seen_at = now();
            $participant->left_at = null;
            $participant->save();

            return $participant;
        });
    }
}
