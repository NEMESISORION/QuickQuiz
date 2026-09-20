<?php

namespace App\Domain\Attempts;

use App\Enums\QuizAttemptStatus;
use App\Models\QuizAttempt;

class ExpireQuizAttempt
{
    public function handle(QuizAttempt $attempt): bool
    {
        if ($attempt->status !== QuizAttemptStatus::InProgress
            || $attempt->expires_at === null
            || $attempt->expires_at->isFuture()) {
            return false;
        }

        $attempt->update(['status' => QuizAttemptStatus::Expired]);

        return true;
    }
}
