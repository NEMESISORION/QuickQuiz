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
    public function __construct(private SubmitQuizAttempt $submitQuizAttempt) {}

    public function handle(QuizAttempt $attempt, QuizAttemptQuestion $question, int $optionId): QuizAttemptAnswer
    {
        $failureMessage = null;
        $shouldFinalize = false;
        $answer = DB::transaction(function () use ($attempt, $question, $optionId, &$failureMessage, &$shouldFinalize): ?QuizAttemptAnswer {
            $lockedAttempt = QuizAttempt::query()->lockForUpdate()->findOrFail($attempt->getKey());

            if ($lockedAttempt->status !== QuizAttemptStatus::InProgress) {
                $failureMessage = 'This attempt is no longer active.';

                return null;
            }

            if ($lockedAttempt->expires_at !== null && ! $lockedAttempt->expires_at->isFuture()) {
                $failureMessage = 'Time has expired for this attempt.';
                $shouldFinalize = true;

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
            if ($shouldFinalize) {
                $this->submitQuizAttempt->handle($attempt);
            }

            throw ValidationException::withMessages(['attempt' => $failureMessage]);
        }

        return $answer;
    }
}
