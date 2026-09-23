<?php

namespace App\Http\Controllers;

use App\Enums\QuizStatus;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class QuizArchiveController extends Controller
{
    public function __invoke(Quiz $quiz): RedirectResponse
    {
        DB::transaction(function () use ($quiz): void {
            $lockedQuiz = Quiz::query()->lockForUpdate()->findOrFail($quiz->getKey());
            Gate::authorize('archive', $lockedQuiz);
            $lockedQuiz->update(['status' => QuizStatus::Archived]);
        }, 3);

        return redirect()
            ->route('educator.quizzes.show', $quiz)
            ->with('status', 'Quiz archived. Existing results remain available.');
    }
}
