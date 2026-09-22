<?php

namespace Tests\Feature;

use App\Enums\QuizAttemptStatus;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class EducatorAnalyticsExportTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_educator_exports_only_owned_attempts_with_active_filters(): void
    {
        $educator = User::factory()->educator()->create();
        $quiz = Quiz::factory()->published()->for($educator, 'educator')->create(['title' => '=Formula quiz']);
        $includedLearner = User::factory()->learner()->create([
            'name' => '+Spreadsheet learner',
            'email' => 'included@example.test',
        ]);
        $excludedLearner = User::factory()->learner()->create(['email' => 'excluded@example.test']);
        $otherQuiz = Quiz::factory()->published()->for(User::factory()->educator(), 'educator')->create();

        $this->completedAttempt($quiz, $includedLearner, '2026-09-15 10:00:00');
        $this->completedAttempt($quiz, $excludedLearner, '2026-08-15 10:00:00');
        $this->completedAttempt($otherQuiz, $excludedLearner, '2026-09-15 10:00:00');

        $response = $this->actingAs($educator)->get(route('educator.analytics.export', [
            'quiz_id' => $quiz->getKey(),
            'from' => '2026-09-01',
            'to' => '2026-09-30',
        ]));

        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertDownload('quickquiz-results-'.now()->format('Y-m-d').'.csv');
        $csv = $response->streamedContent();

        $this->assertStringContainsString("'=Formula quiz", $csv);
        $this->assertStringContainsString("'+Spreadsheet learner", $csv);
        $this->assertStringContainsString('included@example.test', $csv);
        $this->assertStringNotContainsString('excluded@example.test', $csv);
    }

    public function test_other_educators_quiz_is_rejected_by_export_filter(): void
    {
        $educator = User::factory()->educator()->create();
        $otherQuiz = Quiz::factory()->published()->for(User::factory()->educator(), 'educator')->create();

        $this->actingAs($educator)
            ->get(route('educator.analytics.export', ['quiz_id' => $otherQuiz->getKey()]))
            ->assertRedirect()
            ->assertSessionHasErrors('quiz_id');
    }

    public function test_learner_cannot_export_educator_results(): void
    {
        $this->actingAs(User::factory()->learner()->create())
            ->get(route('educator.analytics.export'))
            ->assertForbidden();
    }

    private function completedAttempt(Quiz $quiz, User $learner, string $startedAt): QuizAttempt
    {
        return QuizAttempt::factory()->for($quiz)->for($learner, 'learner')->create([
            'status' => QuizAttemptStatus::Submitted,
            'started_at' => $startedAt,
            'submitted_at' => '2026-09-15 10:10:00',
            'score' => 8,
            'max_score' => 10,
            'passed' => true,
        ]);
    }
}
