<?php

namespace App\Http\Controllers;

use App\Domain\Authoring\DuplicateQuiz;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class QuizDuplicateController extends Controller
{
    public function __invoke(Request $request, Quiz $quiz, DuplicateQuiz $duplicateQuiz): RedirectResponse
    {
        Gate::authorize('duplicate', $quiz);
        $educator = $request->user();
        assert($educator instanceof User);

        $duplicate = $duplicateQuiz->handle($quiz, $educator);

        return redirect()
            ->route('educator.quizzes.show', $duplicate)
            ->with('status', 'Editable quiz copy created.');
    }
}
