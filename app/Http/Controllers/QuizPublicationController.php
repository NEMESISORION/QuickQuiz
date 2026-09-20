<?php

namespace App\Http\Controllers;

use App\Domain\Authoring\PublishQuiz;
use App\Http\Requests\PublishQuizRequest;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;

class QuizPublicationController extends Controller
{
    public function store(PublishQuizRequest $request, Quiz $quiz, PublishQuiz $publishQuiz): RedirectResponse
    {
        $mode = $request->string('mode')->toString();
        $publishedQuiz = $publishQuiz->handle(
            quiz: $quiz,
            mode: $mode,
            opensAt: $request->date('opens_at'),
            closesAt: $request->date('closes_at'),
        );

        $status = $publishedQuiz->status->value === 'scheduled'
            ? 'Quiz scheduled successfully.'
            : 'Quiz published successfully.';

        return redirect()
            ->route('educator.quizzes.show', $publishedQuiz)
            ->with('status', $status);
    }
}
