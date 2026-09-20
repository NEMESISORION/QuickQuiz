<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class QuizPreviewController extends Controller
{
    public function __invoke(Quiz $quiz): View
    {
        Gate::authorize('view', $quiz);

        $quiz->load('questions.answerOptions');

        return view('educator.quizzes.preview', ['quiz' => $quiz]);
    }
}
