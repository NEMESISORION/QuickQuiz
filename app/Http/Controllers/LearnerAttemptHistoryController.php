<?php

namespace App\Http\Controllers;

use App\Enums\AttemptHistoryFilter;
use App\Http\Requests\LearnerAttemptHistoryRequest;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\View\View;

class LearnerAttemptHistoryController extends Controller
{
    public function __invoke(LearnerAttemptHistoryRequest $request): View
    {
        $user = $request->user();
        assert($user instanceof User);
        $filter = AttemptHistoryFilter::tryFrom($request->string('status')->toString())
            ?? AttemptHistoryFilter::All;

        $attempts = $filter->apply(
            QuizAttempt::query()->whereBelongsTo($user, 'learner')->with('quiz'),
        )
            ->latest('started_at')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('learner.attempts.index', [
            'attempts' => $attempts,
            'filter' => $filter,
            'filters' => AttemptHistoryFilter::cases(),
        ]);
    }
}
