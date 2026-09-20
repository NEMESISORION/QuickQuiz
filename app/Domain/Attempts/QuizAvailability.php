<?php

namespace App\Domain\Attempts;

use App\Enums\QuizAttemptStatus;
use App\Enums\QuizStatus;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Carbon;

class QuizAvailability
{
    public function isOpen(Quiz $quiz, ?Carbon $at = null): bool
    {
        $at ??= now();

        return in_array($quiz->status, [QuizStatus::Published, QuizStatus::Scheduled], true)
            && ($quiz->opens_at === null || $quiz->opens_at->lessThanOrEqualTo($at))
            && ($quiz->closes_at === null || $quiz->closes_at->isAfter($at));
    }

    public function isUpcoming(Quiz $quiz, ?Carbon $at = null): bool
    {
        $at ??= now();

        return $quiz->status === QuizStatus::Scheduled
            && $quiz->opens_at !== null
            && $quiz->opens_at->isAfter($at);
    }

    public function activeAttempt(Quiz $quiz, User $learner, ?Carbon $at = null): ?QuizAttempt
    {
        $at ??= now();

        return $quiz->attempts()
            ->whereBelongsTo($learner, 'learner')
            ->where('status', QuizAttemptStatus::InProgress)
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', $at))
            ->latest('started_at')
            ->first();
    }

    public function attemptsRemaining(Quiz $quiz, User $learner): int
    {
        $used = $quiz->attempts()->whereBelongsTo($learner, 'learner')->count();

        return max(0, $quiz->max_attempts - $used);
    }
}
