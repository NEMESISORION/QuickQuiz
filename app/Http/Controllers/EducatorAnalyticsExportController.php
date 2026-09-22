<?php

namespace App\Http\Controllers;

use App\Domain\Analytics\StreamEducatorResultsCsv;
use App\Http\Requests\EducatorAnalyticsRequest;
use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EducatorAnalyticsExportController extends Controller
{
    public function __invoke(
        EducatorAnalyticsRequest $request,
        StreamEducatorResultsCsv $streamEducatorResultsCsv,
    ): StreamedResponse {
        $educator = $request->user();
        assert($educator instanceof User);
        $quizId = $request->filled('quiz_id') ? $request->integer('quiz_id') : null;
        $from = $request->filled('from') ? $request->date('from') : null;
        $to = $request->filled('to') ? $request->date('to') : null;

        return response()->streamDownload(function () use ($streamEducatorResultsCsv, $educator, $quizId, $from, $to): void {
            $output = fopen('php://output', 'wb');

            if ($output === false) {
                return;
            }

            try {
                $streamEducatorResultsCsv->write($educator, $quizId, $from, $to, $output);
            } finally {
                fclose($output);
            }
        }, 'quickquiz-results-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
