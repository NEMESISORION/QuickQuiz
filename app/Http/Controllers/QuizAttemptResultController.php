<?php

namespace App\Http\Controllers;

use App\Domain\Attempts\ExpireQuizAttempt;
use App\Domain\Attempts\QuizAttemptReview;
use App\Domain\Attempts\SubmitQuizAttempt;
use App\Enums\QuizAttemptStatus;
use App\Models\QuizAttempt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class QuizAttemptResultController extends Controller
{
    public function __invoke(
        QuizAttempt $quizAttempt,
        ExpireQuizAttempt $expireQuizAttempt,
        SubmitQuizAttempt $submitQuizAttempt,
        QuizAttemptReview $review,
    ): View|RedirectResponse {
        Gate::authorize('view', $quizAttempt);
        $expireQuizAttempt->handle($quizAttempt);
        $quizAttempt->refresh();

        if ($quizAttempt->status === QuizAttemptStatus::InProgress) {
            return redirect()->route('learner.attempts.show', $quizAttempt);
        }

        $quizAttempt = $submitQuizAttempt->handle($quizAttempt)->load('quiz');

        $answersAreVisible = $review->answersAreVisible($quizAttempt);
        if ($answersAreVisible) {
            $quizAttempt->load(['questions.options', 'answers.option']);
        }

        $percentage = $quizAttempt->max_score > 0
            ? (int) round(($quizAttempt->score / $quizAttempt->max_score) * 100)
            : 0;

        return view('learner.attempts.result', [
            'attempt' => $quizAttempt,
            'percentage' => $percentage,
            'answersAreVisible' => $answersAreVisible,
            'reviewMessage' => $answersAreVisible ? '' : $review->lockedMessage($quizAttempt),
        ]);
    }
}
