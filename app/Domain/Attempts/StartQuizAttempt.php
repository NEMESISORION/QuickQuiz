<?php

namespace App\Domain\Attempts;

use App\Enums\QuizAttemptStatus;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StartQuizAttempt
{
    public function __construct(private QuizAvailability $availability) {}

    public function handle(Quiz $quiz, User $learner): QuizAttempt
    {
        return DB::transaction(function () use ($quiz, $learner): QuizAttempt {
            $lockedQuiz = Quiz::query()->lockForUpdate()->findOrFail($quiz->getKey());
            User::query()->lockForUpdate()->findOrFail($learner->getKey());
            $now = now();

            $lockedQuiz->attempts()
                ->whereBelongsTo($learner, 'learner')
                ->where('status', QuizAttemptStatus::InProgress)
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', $now)
                ->update(['status' => QuizAttemptStatus::Expired]);

            $activeAttempt = $this->availability->activeAttempt($lockedQuiz, $learner, $now);
            if ($activeAttempt !== null) {
                return $activeAttempt;
            }

            if (! $this->availability->isOpen($lockedQuiz, $now)) {
                throw ValidationException::withMessages(['quiz' => 'This quiz is not open for attempts.']);
            }

            $attemptNumber = $lockedQuiz->attempts()->whereBelongsTo($learner, 'learner')->count() + 1;
            if ($attemptNumber > $lockedQuiz->max_attempts) {
                throw ValidationException::withMessages(['quiz' => 'You have used all attempts for this quiz.']);
            }

            $questions = $lockedQuiz->questions()->with('answerOptions')->get();
            if ($questions->isEmpty()) {
                throw ValidationException::withMessages(['quiz' => 'This quiz has no questions available.']);
            }

            $expiresAt = $this->expiresAt($lockedQuiz, $now);
            $attempt = $learner->quizAttempts()->make([
                'status' => QuizAttemptStatus::InProgress,
                'attempt_number' => $attemptNumber,
                'started_at' => $now,
                'expires_at' => $expiresAt,
                'duration_minutes_snapshot' => $lockedQuiz->duration_minutes,
                'pass_percentage_snapshot' => $lockedQuiz->pass_percentage,
                'review_policy_snapshot' => $lockedQuiz->review_policy,
                'review_available_at_snapshot' => $lockedQuiz->closes_at,
                'max_score' => $questions->sum('points'),
            ]);
            $attempt->quiz()->associate($lockedQuiz);
            $attempt->save();

            $this->snapshotQuestions($attempt, $questions, $lockedQuiz);

            return $attempt->load('questions.options');
        }, 3);
    }

    private function expiresAt(Quiz $quiz, Carbon $startedAt): ?Carbon
    {
        $expiresAt = $quiz->duration_minutes === null
            ? null
            : $startedAt->copy()->addMinutes($quiz->duration_minutes);

        if ($quiz->closes_at !== null && ($expiresAt === null || $quiz->closes_at->isBefore($expiresAt))) {
            return $quiz->closes_at->copy();
        }

        return $expiresAt;
    }

    /** @param Collection<int, Question> $questions */
    private function snapshotQuestions(QuizAttempt $attempt, Collection $questions, Quiz $quiz): void
    {
        if ($quiz->shuffle_questions) {
            $questions = $questions->shuffle()->values();
        }

        $questions->each(function (Question $question, int $index) use ($attempt, $quiz): void {
            $snapshot = $attempt->questions()->make([
                'type' => $question->type,
                'prompt' => $question->prompt,
                'explanation' => $question->explanation,
                'points' => $question->points,
                'position' => $index + 1,
            ]);
            $snapshot->sourceQuestion()->associate($question);
            $snapshot->save();

            $options = $quiz->shuffle_answers
                ? $question->answerOptions->shuffle()->values()
                : $question->answerOptions;

            $options->each(function ($option, int $optionIndex) use ($snapshot): void {
                $optionSnapshot = $snapshot->options()->make([
                    'content' => $option->content,
                    'is_correct' => $option->is_correct,
                    'position' => $optionIndex + 1,
                ]);
                $optionSnapshot->sourceAnswerOption()->associate($option);
                $optionSnapshot->save();
            });
        });
    }
}
