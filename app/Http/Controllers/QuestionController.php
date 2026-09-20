<?php

namespace App\Http\Controllers;

use App\Domain\Authoring\SaveQuestion;
use App\Enums\QuestionType;
use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\UpdateQuestionRequest;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class QuestionController extends Controller
{
    public function create(Quiz $quiz): View
    {
        Gate::authorize('create', [Question::class, $quiz]);

        return view('educator.questions.create', [
            'quiz' => $quiz,
            'question' => new Question([
                'type' => QuestionType::MultipleChoice,
                'points' => 1,
            ]),
            'questionTypes' => QuestionType::cases(),
        ]);
    }

    public function store(StoreQuestionRequest $request, Quiz $quiz, SaveQuestion $saveQuestion): RedirectResponse
    {
        $question = $saveQuestion->handle($quiz, $request->validated());

        return redirect()
            ->route('educator.quizzes.show', $quiz)
            ->with('status', "Question {$question->position} added.");
    }

    public function edit(Quiz $quiz, Question $question): View
    {
        Gate::authorize('update', $question);

        return view('educator.questions.edit', [
            'quiz' => $quiz,
            'question' => $question->load('answerOptions'),
            'questionTypes' => QuestionType::cases(),
        ]);
    }

    public function update(
        UpdateQuestionRequest $request,
        Quiz $quiz,
        Question $question,
        SaveQuestion $saveQuestion,
    ): RedirectResponse {
        $saveQuestion->handle($quiz, $request->validated(), $question);

        return redirect()
            ->route('educator.quizzes.show', $quiz)
            ->with('status', "Question {$question->position} updated.");
    }

    public function destroy(Quiz $quiz, Question $question): RedirectResponse
    {
        Gate::authorize('delete', $question);

        DB::transaction(function () use ($quiz, $question): void {
            $deletedPosition = $question->position;
            $question->delete();
            $quiz->questions()->where('position', '>', $deletedPosition)->decrement('position');
        });

        return redirect()
            ->route('educator.quizzes.show', $quiz)
            ->with('status', 'Question removed.');
    }
}
