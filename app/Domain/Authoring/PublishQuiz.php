<?php

namespace App\Domain\Authoring;

use App\Enums\QuizStatus;
use App\Models\Quiz;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PublishQuiz
{
    public function handle(Quiz $quiz, string $mode, ?Carbon $opensAt, ?Carbon $closesAt): Quiz
    {
        return DB::transaction(function () use ($quiz, $mode, $opensAt, $closesAt): Quiz {
            $lockedQuiz = Quiz::query()->lockForUpdate()->findOrFail($quiz->getKey());
            $scheduled = $mode === 'scheduled';

            $lockedQuiz->update([
                'status' => $scheduled ? QuizStatus::Scheduled : QuizStatus::Published,
                'opens_at' => $scheduled ? $opensAt : now(),
                'closes_at' => $closesAt,
                'published_at' => $scheduled ? null : now(),
                'closed_at' => null,
            ]);

            return $lockedQuiz;
        });
    }
}
