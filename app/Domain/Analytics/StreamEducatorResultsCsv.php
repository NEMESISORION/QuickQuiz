<?php

namespace App\Domain\Analytics;

use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class StreamEducatorResultsCsv
{
    /** @param resource $output */
    public function write(User $educator, ?int $quizId, ?Carbon $from, ?Carbon $to, $output): void
    {
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, [
            'Quiz', 'Learner', 'Email', 'Attempt', 'Status', 'Score', 'Maximum score',
            'Percentage', 'Passed', 'Started at', 'Submitted at', 'Duration minutes',
        ]);

        QuizAttempt::query()
            ->whereHas('quiz', fn (Builder $query) => $query->where('educator_id', $educator->getKey()))
            ->when($quizId !== null, fn (Builder $query) => $query->where('quiz_id', $quizId))
            ->when($from !== null, fn (Builder $query) => $query->where('started_at', '>=', $from?->copy()->startOfDay()))
            ->when($to !== null, fn (Builder $query) => $query->where('started_at', '<=', $to?->copy()->endOfDay()))
            ->with(['quiz:id,title', 'learner:id,name,email'])
            ->orderBy('id')
            ->lazyById(500)
            ->each(function (QuizAttempt $attempt) use ($output): void {
                $duration = $attempt->submitted_at === null
                    ? null
                    : (int) round($attempt->started_at->diffInSeconds($attempt->submitted_at) / 60);
                $percentage = $attempt->score !== null && $attempt->max_score > 0
                    ? (int) round(($attempt->score / $attempt->max_score) * 100)
                    : null;

                fputcsv($output, array_map($this->escapeSpreadsheetCell(...), [
                    $attempt->quiz->title,
                    $attempt->learner->name,
                    $attempt->learner->email,
                    $attempt->attempt_number,
                    str_replace('_', ' ', $attempt->status->value),
                    $attempt->score,
                    $attempt->max_score,
                    $percentage,
                    $attempt->passed === null ? '' : ($attempt->passed ? 'Yes' : 'No'),
                    $attempt->started_at->toIso8601String(),
                    $attempt->submitted_at?->toIso8601String(),
                    $duration,
                ]));
            });
    }

    private function escapeSpreadsheetCell(mixed $value): string|int
    {
        if (! is_string($value)) {
            return $value ?? '';
        }

        return preg_match('/^\s*[=+\-@]/u', $value) === 1 ? "'".$value : $value;
    }
}
