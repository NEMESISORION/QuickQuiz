<?php

namespace App\Domain\Attempts;

use App\Domain\Certificates\IssueCertificate;
use App\Domain\Notifications\SendAttemptCompletionNotifications;
use App\Enums\QuizAttemptStatus;
use App\Models\QuizAttempt;
use Illuminate\Support\Facades\DB;

class SubmitQuizAttempt
{
    public function __construct(
        private ScoreQuizAttempt $scoreQuizAttempt,
        private IssueCertificate $issueCertificate,
        private SendAttemptCompletionNotifications $sendAttemptCompletionNotifications,
    ) {}

    public function handle(QuizAttempt $attempt): QuizAttempt
    {
        return DB::transaction(function () use ($attempt): QuizAttempt {
            $lockedAttempt = QuizAttempt::query()->lockForUpdate()->findOrFail($attempt->getKey());

            if ($lockedAttempt->status !== QuizAttemptStatus::InProgress) {
                $completedNow = false;

                if ($lockedAttempt->score === null) {
                    $lockedAttempt = $this->scoreQuizAttempt->handle($lockedAttempt, $lockedAttempt->status);
                    $completedNow = true;
                }

                $certificate = $this->issueCertificate->handle($lockedAttempt);
                $this->sendAttemptCompletionNotifications->handle($lockedAttempt, $certificate, $completedNow);

                return $lockedAttempt;
            }

            $status = $lockedAttempt->expires_at !== null && ! $lockedAttempt->expires_at->isFuture()
                ? QuizAttemptStatus::Expired
                : QuizAttemptStatus::Submitted;

            $lockedAttempt = $this->scoreQuizAttempt->handle($lockedAttempt, $status);
            $certificate = $this->issueCertificate->handle($lockedAttempt);
            $this->sendAttemptCompletionNotifications->handle($lockedAttempt, $certificate, true);

            return $lockedAttempt;
        }, 3);
    }
}
