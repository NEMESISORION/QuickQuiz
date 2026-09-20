<?php

namespace App\Http\Controllers;

use App\Domain\Attempts\SaveQuizAttemptAnswer;
use App\Http\Requests\SaveQuizAttemptAnswerRequest;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class QuizAttemptAnswerController extends Controller
{
    public function __invoke(
        SaveQuizAttemptAnswerRequest $request,
        QuizAttempt $quizAttempt,
        QuizAttemptQuestion $quizAttemptQuestion,
        SaveQuizAttemptAnswer $saveQuizAttemptAnswer,
    ): JsonResponse {
        Gate::authorize('view', $quizAttempt);

        $answer = $saveQuizAttemptAnswer->handle(
            $quizAttempt,
            $quizAttemptQuestion,
            $request->integer('option_id'),
        );

        return response()->json([
            'saved_at' => $answer->answered_at->toIso8601String(),
            'answered_count' => $quizAttempt->answers()->count(),
            'total_questions' => $quizAttempt->questions()->count(),
        ]);
    }
}
