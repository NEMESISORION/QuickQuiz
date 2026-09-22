<?php

namespace Tests\Feature;

use App\Domain\Attempts\SubmitQuizAttempt;
use App\Enums\QuizAttemptStatus;
use App\Models\Certificate;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\QuizAttemptOption;
use App\Models\QuizAttemptQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CertificateTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_passing_an_enabled_quiz_issues_one_certificate_idempotently(): void
    {
        $quiz = Quiz::factory()->published()->create(['certificates_enabled' => true]);
        $attempt = $this->answerAttempt($quiz, true);
        $action = app(SubmitQuizAttempt::class);

        $action->handle($attempt);
        $action->handle($attempt->fresh());

        $this->assertDatabaseCount('certificates', 1);
        $this->assertDatabaseHas('certificates', ['quiz_attempt_id' => $attempt->getKey()]);
    }

    public function test_failed_or_disabled_quizzes_do_not_issue_certificates(): void
    {
        $failedAttempt = $this->answerAttempt(
            Quiz::factory()->published()->create(['certificates_enabled' => true]),
            false,
        );
        $disabledAttempt = $this->answerAttempt(
            Quiz::factory()->published()->create(['certificates_enabled' => false]),
            true,
        );

        app(SubmitQuizAttempt::class)->handle($failedAttempt);
        app(SubmitQuizAttempt::class)->handle($disabledAttempt);

        $this->assertDatabaseCount('certificates', 0);
    }

    public function test_public_certificate_is_verifiable_and_escapes_untrusted_content(): void
    {
        $this->withoutVite();
        $learner = User::factory()->learner()->create(['name' => '<script>Person</script>']);
        $quiz = Quiz::factory()->published()->create(['title' => '<script>Quiz</script>']);
        $attempt = QuizAttempt::factory()->for($quiz)->for($learner, 'learner')->create([
            'status' => QuizAttemptStatus::Submitted,
            'score' => 8,
            'max_score' => 10,
            'passed' => true,
            'submitted_at' => now(),
        ]);
        $certificate = Certificate::factory()->for($attempt, 'attempt')->create();

        $this->get(route('certificates.show', $certificate->verification_code))
            ->assertOk()
            ->assertSeeText('Verified achievement')
            ->assertSeeText('80%')
            ->assertSee('&lt;script&gt;Person&lt;/script&gt;', false)
            ->assertDontSee('<script>Person</script>', false)
            ->assertSee($certificate->verification_code);
    }

    public function test_revoked_or_unknown_certificate_is_not_publicly_visible(): void
    {
        $revoked = Certificate::factory()->create(['revoked_at' => now()]);

        $this->get(route('certificates.show', $revoked->verification_code))->assertNotFound();
        $this->get(route('certificates.show', '00000000-0000-0000-0000-000000000000'))->assertNotFound();
    }

    private function answerAttempt(Quiz $quiz, bool $correct): QuizAttempt
    {
        $attempt = QuizAttempt::factory()->for($quiz)->create([
            'max_score' => 1,
            'pass_percentage_snapshot' => 100,
        ]);
        $question = QuizAttemptQuestion::factory()->for($attempt, 'attempt')->create(['points' => 1]);
        $option = QuizAttemptOption::factory()->for($question, 'question')->create(['is_correct' => $correct]);
        $answer = new QuizAttemptAnswer(['answered_at' => now()]);
        $answer->attempt()->associate($attempt);
        $answer->question()->associate($question);
        $answer->option()->associate($option);
        $answer->save();

        return $attempt;
    }
}
