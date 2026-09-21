<?php

namespace App\Domain\Attempts;

use App\Enums\QuizAttemptStatus;
use App\Models\QuizAttempt;
use Illuminate\Support\Facades\DB;

class SubmitQuizAttempt
{
    public function __construct(private ScoreQuizAttempt $scoreQuizAttempt) {}

    public function handle(QuizAttempt $attempt): QuizAttempt
    {
        return DB::transaction(function () use ($attempt): QuizAttempt {
            $lockedAttempt = QuizAttempt::query()->lockForUpdate()->findOrFail($attempt->getKey());

            if ($lockedAttempt->status !== QuizAttemptStatus::InProgress) {
                if ($lockedAttempt->score === null) {
                    return $this->scoreQuizAttempt->handle($lockedAttempt, $lockedAttempt->status);
                }

                return $lockedAttempt;
            }

            $status = $lockedAttempt->expires_at !== null && ! $lockedAttempt->expires_at->isFuture()
                ? QuizAttemptStatus::Expired
                : QuizAttemptStatus::Submitted;

            return $this->scoreQuizAttempt->handle($lockedAttempt, $status);
        }, 3);
    }
}
