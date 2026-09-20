<?php

namespace App\Http\Controllers;

use App\Domain\Attempts\QuizAvailability;
use App\Enums\QuizStatus;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LearnerQuizController extends Controller
{
    public function __construct(private QuizAvailability $availability) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        assert($user instanceof User);

        $quizzes = Quiz::query()
            ->whereIn('status', [QuizStatus::Published, QuizStatus::Scheduled])
            ->where(fn ($query) => $query->whereNull('closes_at')->orWhere('closes_at', '>', now()))
            ->withCount('questions')
            ->orderBy('opens_at')
            ->orderBy('id')
            ->get();

        $attempts = $user->quizAttempts()
            ->whereIn('quiz_id', $quizzes->modelKeys())
            ->get()
            ->groupBy('quiz_id');

        return view('learner.quizzes.index', [
            'availableQuizzes' => $quizzes->filter(fn (Quiz $quiz): bool => $this->availability->isOpen($quiz)),
            'upcomingQuizzes' => $quizzes->filter(fn (Quiz $quiz): bool => $this->availability->isUpcoming($quiz)),
            'attempts' => $attempts,
        ]);
    }

    public function show(Request $request, Quiz $quiz): View
    {
        Gate::authorize('discover', $quiz);
        $user = $request->user();
        assert($user instanceof User);

        $quiz->loadCount('questions');

        return view('learner.quizzes.show', [
            'quiz' => $quiz,
            'isOpen' => $this->availability->isOpen($quiz),
            'isUpcoming' => $this->availability->isUpcoming($quiz),
            'activeAttempt' => $this->availability->activeAttempt($quiz, $user),
            'attemptsRemaining' => $this->availability->attemptsRemaining($quiz, $user),
        ]);
    }
}
