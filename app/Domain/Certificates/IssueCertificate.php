<?php

namespace App\Domain\Certificates;

use App\Models\Certificate;
use App\Models\QuizAttempt;
use Illuminate\Support\Str;

class IssueCertificate
{
    public function handle(QuizAttempt $attempt): ?Certificate
    {
        $attempt->loadMissing('quiz');

        if (! $attempt->passed || ! $attempt->quiz->certificates_enabled) {
            return null;
        }

        return $attempt->certificate()->firstOrCreate(
            [],
            [
                'verification_code' => (string) Str::uuid(),
                'issued_at' => now(),
            ],
        );
    }
}
