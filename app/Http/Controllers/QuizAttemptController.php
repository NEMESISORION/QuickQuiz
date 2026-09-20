<?php

namespace App\Http\Controllers;

use App\Domain\Attempts\ExpireQuizAttempt;
use App\Domain\Attempts\StartQuizAttempt;
use App\Enums\QuizAttemptStatus;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class QuizAttemptController extends Controller
{
    public function store(Request $request, Quiz $quiz, StartQuizAttempt $startQuizAttempt): RedirectResponse
    {
        Gate::authorize('startAttempt', $quiz);
        $user = $request->user();
        assert($user instanceof User);

        $attempt = $startQuizAttempt->handle($quiz, $user);

        return redirect()
            ->route('learner.attempts.show', $attempt)
            ->with('status', 'Your attempt is ready.');
    }

    public function show(QuizAttempt $quizAttempt, ExpireQuizAttempt $expireQuizAttempt): View|RedirectResponse
    {
        Gate::authorize('view', $quizAttempt);
        if ($expireQuizAttempt->handle($quizAttempt)) {
            return redirect()
                ->route('learner.quizzes.show', $quizAttempt->quiz_id)
                ->withErrors(['quiz' => 'Time has expired for this attempt.']);
        }

        if ($quizAttempt->status !== QuizAttemptStatus::InProgress) {
            return redirect()
                ->route('learner.quizzes.show', $quizAttempt->quiz_id)
                ->withErrors(['quiz' => 'This attempt is no longer active.']);
        }

        $quizAttempt->load(['quiz', 'questions.options', 'answers']);

        return view('learner.attempts.show', ['attempt' => $quizAttempt]);
    }
}
