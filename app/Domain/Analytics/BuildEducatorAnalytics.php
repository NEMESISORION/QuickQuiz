<?php

namespace App\Domain\Analytics;

use App\Enums\QuizAttemptStatus;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BuildEducatorAnalytics
{
    /**
     * @return array{
     *   summary: array{attempts: int, completed: int, completion_rate: int, average_score: int, best_score: int, pass_rate: int, average_duration_minutes: int},
     *   trend: Collection<int, array{date: string, label: string, attempts: int, average_score: int}>,
     *   questions: Collection<int, array{prompt: string, quiz_title: string, presentations: int, responses: int, correctness: int, options: array<int, array{content: string, selected: int, percentage: int, is_correct: bool}>}>,
     *   quizzes: Collection<int, Quiz>
     * }
     */
    public function build(User $educator, ?int $quizId, ?Carbon $from, ?Carbon $to): array
    {
        $quizzes = $educator->quizzes()->select(['id', 'title'])->orderBy('title')->get();
        $quizIds = $quizzes->pluck('id');
        $attemptQuery = QuizAttempt::query()
            ->whereIn('quiz_id', $quizIds)
            ->when($quizId !== null, fn (Builder $query) => $query->where('quiz_id', $quizId));
        $this->applyDateRange($attemptQuery, $from, $to);

        $attemptCount = (clone $attemptQuery)->count();
        $completedAttempts = (clone $attemptQuery)
            ->whereIn('status', [QuizAttemptStatus::Submitted, QuizAttemptStatus::Expired])
            ->get(['score', 'max_score', 'passed', 'started_at', 'submitted_at']);
        $completedCount = $completedAttempts->count();
        $percentages = $completedAttempts
            ->filter(fn (QuizAttempt $attempt): bool => $attempt->max_score > 0)
            ->map(fn (QuizAttempt $attempt): float => (($attempt->score ?? 0) / $attempt->max_score) * 100);
        $durations = $completedAttempts
            ->filter(fn (QuizAttempt $attempt): bool => $attempt->submitted_at !== null)
            ->map(fn (QuizAttempt $attempt): float => $attempt->started_at->diffInSeconds($attempt->submitted_at) / 60);

        return [
            'summary' => [
                'attempts' => $attemptCount,
                'completed' => $completedCount,
                'completion_rate' => $attemptCount > 0 ? (int) round(($completedCount / $attemptCount) * 100) : 0,
                'average_score' => $percentages->isNotEmpty() ? (int) round((float) $percentages->average()) : 0,
                'best_score' => $percentages->isNotEmpty() ? (int) round((float) $percentages->max()) : 0,
                'pass_rate' => $completedCount > 0
                    ? (int) round(($completedAttempts->where('passed', true)->count() / $completedCount) * 100)
                    : 0,
                'average_duration_minutes' => $durations->isNotEmpty() ? (int) round((float) $durations->average()) : 0,
            ],
            'trend' => $this->trend($completedAttempts),
            'questions' => $this->questionAnalysis($quizIds->all(), $quizzes->pluck('title', 'id'), $quizId, $from, $to),
            'quizzes' => $quizzes,
        ];
    }

    /** @param Builder<QuizAttempt> $query */
    private function applyDateRange(Builder $query, ?Carbon $from, ?Carbon $to): void
    {
        $query
            ->when($from !== null, fn (Builder $builder) => $builder->where('started_at', '>=', $from?->copy()->startOfDay()))
            ->when($to !== null, fn (Builder $builder) => $builder->where('started_at', '<=', $to?->copy()->endOfDay()));
    }

    /**
     * @param Collection<int, QuizAttempt> $attempts
     * @return Collection<int, array{date: string, label: string, attempts: int, average_score: int}>
     */
    private function trend(Collection $attempts): Collection
    {
        return $attempts
            ->filter(fn (QuizAttempt $attempt): bool => $attempt->submitted_at !== null && $attempt->max_score > 0)
            ->groupBy(fn (QuizAttempt $attempt): string => $attempt->submitted_at?->format('Y-m-d') ?? '')
            ->sortKeys()
            ->take(-14)
            ->map(function (Collection $dailyAttempts, string $date): array {
                $average = $dailyAttempts->average(
                    fn (QuizAttempt $attempt): float => (($attempt->score ?? 0) / $attempt->max_score) * 100,
                );

                return [
                    'date' => $date,
                    'label' => Carbon::parse($date)->format('M j'),
                    'attempts' => $dailyAttempts->count(),
                    'average_score' => (int) round((float) $average),
                ];
            })
            ->values();
    }

    /**
     * @param array<int, int> $quizIds
     * @param Collection<int, string> $quizTitles
     * @return Collection<int, array{prompt: string, quiz_title: string, presentations: int, responses: int, correctness: int, options: array<int, array{content: string, selected: int, percentage: int, is_correct: bool}>}>
     */
    private function questionAnalysis(
        array $quizIds,
        Collection $quizTitles,
        ?int $quizId,
        ?Carbon $from,
        ?Carbon $to,
    ): Collection {
        if ($quizIds === []) {
            return collect();
        }

        $query = DB::table('quiz_attempt_options')
            ->join('quiz_attempt_questions', 'quiz_attempt_questions.id', '=', 'quiz_attempt_options.quiz_attempt_question_id')
            ->join('quiz_attempts', 'quiz_attempts.id', '=', 'quiz_attempt_questions.quiz_attempt_id')
            ->leftJoin('quiz_attempt_answers', function ($join): void {
                $join->on('quiz_attempt_answers.quiz_attempt_question_id', '=', 'quiz_attempt_questions.id')
                    ->on('quiz_attempt_answers.quiz_attempt_option_id', '=', 'quiz_attempt_options.id');
            })
            ->whereIn('quiz_attempts.quiz_id', $quizIds)
            ->whereIn('quiz_attempts.status', [QuizAttemptStatus::Submitted->value, QuizAttemptStatus::Expired->value])
            ->when($quizId !== null, fn ($builder) => $builder->where('quiz_attempts.quiz_id', $quizId))
            ->when($from !== null, fn ($builder) => $builder->where('quiz_attempts.started_at', '>=', $from?->copy()->startOfDay()))
            ->when($to !== null, fn ($builder) => $builder->where('quiz_attempts.started_at', '<=', $to?->copy()->endOfDay()))
            ->select([
                'quiz_attempts.quiz_id',
                'quiz_attempt_questions.source_question_id',
                'quiz_attempt_questions.prompt',
                'quiz_attempt_options.source_answer_option_id',
                'quiz_attempt_options.content',
                'quiz_attempt_options.is_correct',
            ])
            ->selectRaw('COUNT(quiz_attempt_questions.id) as presentation_count')
            ->selectRaw('COUNT(quiz_attempt_answers.id) as selected_count')
            ->groupBy([
                'quiz_attempts.quiz_id',
                'quiz_attempt_questions.source_question_id',
                'quiz_attempt_questions.prompt',
                'quiz_attempt_options.source_answer_option_id',
                'quiz_attempt_options.content',
                'quiz_attempt_options.is_correct',
            ])
            ->get()
            ->map(function (object $row): array {
                $values = (array) $row;

                return [
                    'quiz_id' => (int) $values['quiz_id'],
                    'source_question_id' => $values['source_question_id'] === null ? null : (int) $values['source_question_id'],
                    'prompt' => (string) $values['prompt'],
                    'source_answer_option_id' => $values['source_answer_option_id'] === null ? null : (int) $values['source_answer_option_id'],
                    'content' => (string) $values['content'],
                    'is_correct' => (bool) $values['is_correct'],
                    'presentation_count' => (int) $values['presentation_count'],
                    'selected_count' => (int) $values['selected_count'],
                ];
            });

        return $query
            ->groupBy(fn (array $row): string => $row['quiz_id'].':'.($row['source_question_id'] ?? hash('sha256', $row['prompt'])))
            ->map(function (Collection $rows) use ($quizTitles): array {
                $first = $rows->first();
                assert(is_array($first));
                $presentations = (int) $rows->max('presentation_count');
                $responses = (int) $rows->sum('selected_count');
                $correctResponses = (int) $rows
                    ->filter(fn (array $row): bool => $row['is_correct'])
                    ->sum('selected_count');

                return [
                    'prompt' => $first['prompt'],
                    'quiz_title' => (string) ($quizTitles[$first['quiz_id']] ?? 'Quiz'),
                    'presentations' => $presentations,
                    'responses' => $responses,
                    'correctness' => $responses > 0 ? (int) round(($correctResponses / $responses) * 100) : 0,
                    'options' => $rows->map(fn (array $row): array => [
                        'content' => $row['content'],
                        'selected' => $row['selected_count'],
                        'percentage' => $responses > 0 ? (int) round(($row['selected_count'] / $responses) * 100) : 0,
                        'is_correct' => $row['is_correct'],
                    ])->values()->all(),
                ];
            })
            ->sortBy('correctness')
            ->values();
    }
}
