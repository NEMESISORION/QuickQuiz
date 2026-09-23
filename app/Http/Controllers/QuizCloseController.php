<?php

namespace App\Http\Controllers;

use App\Enums\QuizStatus;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class QuizCloseController extends Controller
{
    public function __invoke(Quiz $quiz): RedirectResponse
    {
        DB::transaction(function () use ($quiz): void {
            $lockedQuiz = Quiz::query()->lockForUpdate()->findOrFail($quiz->getKey());
            Gate::authorize('close', $lockedQuiz);
            $lockedQuiz->update([
                'status' => QuizStatus::Closed,
                'closes_at' => now(),
                'closed_at' => now(),
            ]);
        }, 3);

        return redirect()
            ->route('educator.quizzes.show', $quiz)
            ->with('status', 'Quiz closed to new attempts. Existing attempts can still be finished.');
    }
}
