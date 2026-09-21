<?php

namespace App\Enums;

use App\Models\QuizAttempt;
use Illuminate\Database\Eloquent\Builder;

enum AttemptHistoryFilter: string
{
    case All = 'all';
    case InProgress = 'in_progress';
    case Passed = 'passed';
    case Failed = 'failed';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::All => 'All attempts',
            self::InProgress => 'In progress',
            self::Passed => 'Passed',
            self::Failed => 'Not passed',
            self::Expired => 'Time expired',
        };
    }

    /**
     * @param  Builder<QuizAttempt>  $query
     * @return Builder<QuizAttempt>
     */
    public function apply(Builder $query): Builder
    {
        return match ($this) {
            self::All => $query,
            self::InProgress => $query->where('status', QuizAttemptStatus::InProgress),
            self::Passed => $query->where('passed', true),
            self::Failed => $query->where('passed', false),
            self::Expired => $query->where('status', QuizAttemptStatus::Expired),
        };
    }
}
