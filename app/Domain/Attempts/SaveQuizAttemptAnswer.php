<?php

namespace App\Domain\Attempts;

use App\Enums\QuizAttemptStatus;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\QuizAttemptQuestion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveQuizAttemptAnswer
{
    public function __construct(private ScoreQuizAttempt $scoreQuizAttempt) {}

    public function handle(QuizAttempt $attempt, QuizAttemptQuestion $question, int $optionId): QuizAttemptAnswer
    {
        $failureMessage = null;
        $answer = DB::transaction(function () use ($attempt, $question, $optionId, &$failureMessage): ?QuizAttemptAnswer {
            $lockedAttempt = QuizAttempt::query()->lockForUpdate()->findOrFail($attempt->getKey());

            if ($lockedAttempt->status !== QuizAttemptStatus::InProgress) {
                $failureMessage = 'This attempt is no longer active.';

                return null;
            }

            if ($lockedAttempt->expires_at !== null && ! $lockedAttempt->expires_at->isFuture()) {
                $this->scoreQuizAttempt->handle($lockedAttempt, QuizAttemptStatus::Expired);
                $failureMessage = 'Time has expired for this attempt.';

                return null;
            }

            if ($question->quiz_attempt_id !== $lockedAttempt->getKey()) {
                abort(404);
            }

            $option = $question->options()->findOrFail($optionId);
            $answer = $lockedAttempt->answers()
                ->whereBelongsTo($question, 'question')
                ->first() ?? $lockedAttempt->answers()->make();
            $answer->question()->associate($question);
            $answer->option()->associate($option);
            $answer->answered_at = now();
            $answer->save();

            return $answer;
        }, 3);

        if ($answer === null) {
            throw ValidationException::withMessages(['attempt' => $failureMessage]);
        }

        return $answer;
    }
}
