<?php

namespace App\Notifications;

use App\Models\QuizAttempt;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AttemptCompleted extends Notification
{
    use Queueable;

    public function __construct(public QuizAttempt $attempt) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, int|string> */
    public function toArray(object $notifiable): array
    {
        $this->attempt->loadMissing(['quiz', 'learner']);
        $percentage = $this->attempt->max_score > 0
            ? (int) round((($this->attempt->score ?? 0) / $this->attempt->max_score) * 100)
            : 0;

        return [
            'kind' => 'attempt_completed',
            'quiz_id' => $this->attempt->quiz_id,
            'quiz_title' => $this->attempt->quiz->title,
            'learner_name' => $this->attempt->learner->name,
            'percentage' => $percentage,
            'message' => $this->attempt->learner->name.' completed '.$this->attempt->quiz->title.' with '.$percentage.'%.',
        ];
    }
}
