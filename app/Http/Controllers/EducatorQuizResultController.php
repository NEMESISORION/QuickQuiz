<?php

namespace App\Http\Controllers;

use App\Enums\AttemptHistoryFilter;
use App\Enums\QuizAttemptStatus;
use App\Http\Requests\EducatorQuizResultsRequest;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\View\View;

class EducatorQuizResultController extends Controller
{
    public function index(EducatorQuizResultsRequest $request, Quiz $quiz): View
    {
        $filter = AttemptHistoryFilter::tryFrom($request->string('status')->toString())
            ?? AttemptHistoryFilter::All;

        $attemptQuery = QuizAttempt::query()->whereBelongsTo($quiz)->with('learner');
        $attempts = $filter->apply($attemptQuery)
            ->latest('started_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $completedQuery = $quiz->attempts()
            ->whereIn('status', [QuizAttemptStatus::Submitted, QuizAttemptStatus::Expired]);
        $completedCount = (clone $completedQuery)->count();
        $pointsEarned = (int) (clone $completedQuery)->sum('score');
        $pointsAvailable = (int) (clone $completedQuery)->sum('max_score');
        $passedCount = (clone $completedQuery)->where('passed', true)->count();

        return view('educator.quizzes.results', [
            'quiz' => $quiz,
            'attempts' => $attempts,
            'filter' => $filter,
            'filters' => AttemptHistoryFilter::cases(),
            'summary' => [
                'total' => $quiz->attempts()->count(),
                'completed' => $completedCount,
                'average_percentage' => $pointsAvailable > 0 ? (int) round(($pointsEarned / $pointsAvailable) * 100) : 0,
                'pass_rate' => $completedCount > 0 ? (int) round(($passedCount / $completedCount) * 100) : 0,
            ],
        ]);
    }
}
