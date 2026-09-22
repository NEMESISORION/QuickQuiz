<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\View\View;

class CertificateController extends Controller
{
    public function __invoke(string $verificationCode): View
    {
        $certificate = Certificate::query()
            ->where('verification_code', $verificationCode)
            ->whereNull('revoked_at')
            ->with(['attempt.learner', 'attempt.quiz'])
            ->firstOrFail();
        $attempt = $certificate->attempt;
        $percentage = $attempt->max_score > 0
            ? (int) round((($attempt->score ?? 0) / $attempt->max_score) * 100)
            : 0;

        return view('certificates.show', [
            'certificate' => $certificate,
            'attempt' => $attempt,
            'percentage' => $percentage,
        ]);
    }
}
