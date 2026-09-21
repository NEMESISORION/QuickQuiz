<?php

namespace App\Http\Controllers;

use App\Domain\Attempts\SubmitQuizAttempt;
use App\Models\QuizAttempt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class QuizAttemptSubmissionController extends Controller
{
    public function store(QuizAttempt $quizAttempt, SubmitQuizAttempt $submitQuizAttempt): RedirectResponse
    {
        Gate::authorize('view', $quizAttempt);
        $submitQuizAttempt->handle($quizAttempt);

        return redirect()
            ->route('learner.attempts.result', $quizAttempt)
            ->with('status', 'Your attempt was submitted successfully.');
    }
}
