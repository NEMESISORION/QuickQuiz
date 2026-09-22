<?php

namespace Database\Factories;

use App\Models\Certificate;
use App\Models\QuizAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Certificate> */
class CertificateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'quiz_attempt_id' => QuizAttempt::factory(),
            'verification_code' => (string) Str::uuid(),
            'issued_at' => now(),
            'revoked_at' => null,
        ];
    }
}
