<?php

namespace App\Notifications;

use App\Models\Certificate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CertificateEarned extends Notification
{
    use Queueable;

    public function __construct(public Certificate $certificate) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, string> */
    public function toArray(object $notifiable): array
    {
        $this->certificate->loadMissing('attempt.quiz');

        return [
            'kind' => 'certificate_earned',
            'quiz_title' => $this->certificate->attempt->quiz->title,
            'verification_code' => $this->certificate->verification_code,
            'message' => 'Your certificate for '.$this->certificate->attempt->quiz->title.' is ready.',
        ];
    }
}
