<?php

namespace App\Domain\LiveSessions;

use App\Enums\LiveSessionStatus;
use App\Enums\QuizStatus;
use App\Models\LiveSession;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateLiveSession
{
    public function handle(Quiz $quiz, User $host): LiveSession
    {
        if ($quiz->status !== QuizStatus::Published) {
            throw ValidationException::withMessages([
                'quiz' => 'Only a published quiz can be used for a live session.',
            ]);
        }

        return DB::transaction(function () use ($quiz, $host): LiveSession {
            $existingSession = LiveSession::query()
                ->whereBelongsTo($quiz)
                ->whereBelongsTo($host, 'host')
                ->whereIn('status', [LiveSessionStatus::Waiting, LiveSessionStatus::Active])
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($existingSession instanceof LiveSession) {
                return $existingSession;
            }

            return $quiz->liveSessions()->create([
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
