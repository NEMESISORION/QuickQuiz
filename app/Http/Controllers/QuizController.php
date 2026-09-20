<?php

namespace App\Http\Controllers;

use App\Enums\QuizReviewPolicy;
use App\Enums\QuizStatus;
use App\Http\Requests\StoreQuizRequest;
use App\Http\Requests\UpdateQuizRequest;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Quiz::class);

        $user = $request->user();

        assert($user instanceof User);

        $quizzes = Quiz::query()
            ->whereBelongsTo($user, 'educator')
            ->withCount('questions')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(9);

        return view('educator.quizzes.index', ['quizzes' => $quizzes]);
    }

    public function create(): View
    {
        Gate::authorize('create', Quiz::class);

        return view('educator.quizzes.create', [
            'quiz' => new Quiz([
                'duration_minutes' => 20,
                'pass_percentage' => 70,
                'max_attempts' => 1,
                'review_policy' => QuizReviewPolicy::Immediately,
            ]),
            'reviewPolicies' => QuizReviewPolicy::cases(),
        ]);
    }

    public function store(StoreQuizRequest $request): RedirectResponse
    {
        $user = $request->user();

        assert($user instanceof User);

        $quiz = $user->quizzes()->create([
            ...$request->safe()->only([
                'title',
                'description',
                'duration_minutes',
                'pass_percentage',
                'max_attempts',
                'shuffle_questions',
                'shuffle_answers',
                'review_policy',
            ]),
            'status' => QuizStatus::Draft,
        ]);

        return redirect()
            ->route('educator.quizzes.show', $quiz)
            ->with('status', 'Quiz draft created.');
    }

    public function show(Quiz $quiz): View
    {
        Gate::authorize('view', $quiz);

        $quiz->load([
            'questions' => fn ($query) => $query->withCount('answerOptions'),
        ]);
        $quiz->questions->each(
            fn (Question $question): Question => $question->setRelation('quiz', $quiz),
        );

        return view('educator.quizzes.show', ['quiz' => $quiz]);
    }

    public function edit(Quiz $quiz): View
    {
        Gate::authorize('update', $quiz);

        return view('educator.quizzes.edit', [
            'quiz' => $quiz,
            'reviewPolicies' => QuizReviewPolicy::cases(),
        ]);
    }

    public function update(UpdateQuizRequest $request, Quiz $quiz): RedirectResponse
    {
        $quiz->update($request->safe()->only([
            'title',
            'description',
            'duration_minutes',
            'pass_percentage',
            'max_attempts',
            'shuffle_questions',
            'shuffle_answers',
            'review_policy',
        ]));

        return redirect()
            ->route('educator.quizzes.show', $quiz)
            ->with('status', 'Quiz settings updated.');
    }

    public function destroy(Quiz $quiz): RedirectResponse
    {
        Gate::authorize('delete', $quiz);

        $quiz->delete();

        return redirect()
            ->route('educator.quizzes.index')
            ->with('status', 'Quiz moved to the archive.');
    }
}
