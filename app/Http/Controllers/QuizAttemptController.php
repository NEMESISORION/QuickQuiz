<?php

namespace App\Http\Controllers;

use App\Domain\Attempts\StartQuizAttempt;
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

    public function show(QuizAttempt $quizAttempt): View
    {
        Gate::authorize('view', $quizAttempt);
        $quizAttempt->load(['quiz', 'questions']);

        return view('learner.attempts.show', ['attempt' => $quizAttempt]);
    }
}
