<?php

namespace App\Domain\Notifications;

use App\Models\Certificate;
use App\Models\QuizAttempt;
use App\Notifications\AttemptCompleted;
use App\Notifications\CertificateEarned;

class SendAttemptCompletionNotifications
{
    public function handle(QuizAttempt $attempt, ?Certificate $certificate, bool $completedNow): void
    {
        $attempt->loadMissing(['quiz.educator', 'learner']);

        if ($completedNow && $attempt->quiz->educator->notifications_enabled) {
            $attempt->quiz->educator->notify(new AttemptCompleted($attempt));
        }

        if ($certificate?->wasRecentlyCreated && $attempt->learner->notifications_enabled) {
            $attempt->learner->notify(new CertificateEarned($certificate));
        }
    }
}
