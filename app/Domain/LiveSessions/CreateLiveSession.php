<?php

namespace App\Domain\LiveSessions;

use App\Domain\Attempts\QuizAvailability;
use App\Enums\LiveSessionStatus;
use App\Models\LiveSession;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateLiveSession
{
    public function __construct(private QuizAvailability $availability) {}

    public function handle(Quiz $quiz, User $host): LiveSession
    {
        return DB::transaction(function () use ($quiz, $host): LiveSession {
            $lockedQuiz = Quiz::query()->lockForUpdate()->findOrFail($quiz->getKey());

            if (! $this->availability->isOpen($lockedQuiz)) {
                throw ValidationException::withMessages([
                    'quiz' => 'Only a currently open quiz can be used for a live session.',
                ]);
            }

            $existingSession = LiveSession::query()
                ->whereBelongsTo($lockedQuiz)
                ->whereBelongsTo($host, 'host')
                ->whereIn('status', [LiveSessionStatus::Waiting, LiveSessionStatus::Active])
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($existingSession instanceof LiveSession) {
                return $existingSession;
            }

            return $lockedQuiz->liveSessions()->create([
                'host_id' => $host->getKey(),
                'code' => $this->uniqueCode(),
                'status' => LiveSessionStatus::Waiting,
            ]);
        });
    }

    private function uniqueCode(): string
    {
        $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

        do {
            $code = '';

            for ($position = 0; $position < 6; $position++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (LiveSession::query()->where('code', $code)->exists());

        return $code;
    }
}
