<?php

namespace App\Http\Controllers;

use App\Domain\Analytics\BuildEducatorAnalytics;
use App\Http\Requests\EducatorAnalyticsRequest;
use App\Models\User;
use Illuminate\View\View;

class EducatorAnalyticsController extends Controller
{
    public function __invoke(
        EducatorAnalyticsRequest $request,
        BuildEducatorAnalytics $buildEducatorAnalytics,
    ): View {
        $educator = $request->user();
        assert($educator instanceof User);
        $quizId = $request->filled('quiz_id') ? $request->integer('quiz_id') : null;
        $from = $request->filled('from') ? $request->date('from') : null;
        $to = $request->filled('to') ? $request->date('to') : null;
        $analytics = $buildEducatorAnalytics->build($educator, $quizId, $from, $to);

        return view('educator.analytics.index', [
            ...$analytics,
            'selectedQuizId' => $quizId,
            'from' => $from?->format('Y-m-d'),
            'to' => $to?->format('Y-m-d'),
        ]);
    }
}
