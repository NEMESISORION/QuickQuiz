<?php

namespace App\Domain\Attempts;

use App\Enums\QuizReviewPolicy;
use App\Models\QuizAttempt;
use Illuminate\Support\Carbon;

class QuizAttemptReview
{
    public function answersAreVisible(QuizAttempt $attempt, ?Carbon $at = null): bool
    {
        $at ??= now();

        return match ($attempt->review_policy_snapshot) {
            QuizReviewPolicy::Immediately => true,
            QuizReviewPolicy::AfterClose => $attempt->review_available_at_snapshot?->lessThanOrEqualTo($at) ?? false,
            QuizReviewPolicy::Never => false,
        };
    }

    public function lockedMessage(QuizAttempt $attempt): string
    {
        return match ($attempt->review_policy_snapshot) {
            QuizReviewPolicy::AfterClose => $attempt->review_available_at_snapshot === null
                ? 'Answer review is unavailable until this assessment closes.'
                : 'Answer review unlocks '.$attempt->review_available_at_snapshot->diffForHumans().'.',
            QuizReviewPolicy::Never => 'This assessment does not reveal answer keys.',
            QuizReviewPolicy::Immediately => '',
        };
    }
}
