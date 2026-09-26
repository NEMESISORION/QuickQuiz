<?php

namespace App\Domain\LiveSessions;

use App\Enums\LiveSessionStatus;
use App\Models\AnswerOption;
use App\Models\LiveSession;
use App\Models\LiveSessionParticipant;
use App\Models\LiveSessionResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordLiveAnswer
{
    public function handle(
        LiveSession $liveSession,
        LiveSessionParticipant $participant,
        int $answerOptionId,
    ): LiveSessionResponse {
        return DB::transaction(function () use ($liveSession, $participant, $answerOptionId): LiveSessionResponse {
            $lockedSession = LiveSession::query()->lockForUpdate()->findOrFail($liveSession->getKey());

            if ($lockedSession->status !== LiveSessionStatus::Active || $lockedSession->current_question_id === null) {
                throw ValidationException::withMessages(['answer_option_id' => 'There is no active question to answer.']);
            }

            if ($lockedSession->hasExpired()) {
                throw ValidationException::withMessages(['answer_option_id' => 'Time is up for this live quiz.']);
            }

            $existingResponse = $participant->responses()
                ->where('question_id', $lockedSession->current_question_id)
                ->first();

            if ($existingResponse instanceof LiveSessionResponse) {
                throw ValidationException::withMessages(['answer_option_id' => 'Your answer for this question is already locked.']);
            }

            $answerOption = AnswerOption::query()
                ->whereKey($answerOptionId)
                ->where('question_id', $lockedSession->current_question_id)
                ->first();

            if (! $answerOption instanceof AnswerOption) {
                throw ValidationException::withMessages(['answer_option_id' => 'Choose an answer from the active question.']);
            }

            return $participant->responses()->create([
                'question_id' => $lockedSession->current_question_id,
                'answer_option_id' => $answerOption->getKey(),
                'is_correct' => $answerOption->is_correct,
                'points_awarded' => $answerOption->is_correct ? $answerOption->question->points : 0,
                'answered_at' => now(),
            ]);
        });
    }
}
