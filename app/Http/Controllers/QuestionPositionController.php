<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReorderQuestionRequest;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class QuestionPositionController extends Controller
{
    public function __invoke(ReorderQuestionRequest $request, Quiz $quiz, Question $question): RedirectResponse
    {
        $direction = $request->string('direction')->toString();
        $operator = $direction === 'up' ? '<' : '>';
        $order = $direction === 'up' ? 'desc' : 'asc';

        $adjacentQuestion = $quiz->questions()
            ->where('position', $operator, $question->position)
            ->orderBy('position', $order)
            ->first();

        if ($adjacentQuestion instanceof Question) {
            DB::transaction(function () use ($question, $adjacentQuestion): void {
                $originalPosition = $question->position;
                $adjacentPosition = $adjacentQuestion->position;
                $question->update(['position' => 0]);
                $adjacentQuestion->update(['position' => $originalPosition]);
                $question->update(['position' => $adjacentPosition]);
            });
        }

        return redirect()->route('educator.quizzes.show', $quiz);
    }
}
