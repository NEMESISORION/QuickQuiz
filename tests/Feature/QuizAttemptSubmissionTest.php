<?php

namespace Tests\Feature;

use App\Domain\Attempts\SubmitQuizAttempt;
use App\Enums\QuizAttemptStatus;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\QuizAttemptOption;
use App\Models\QuizAttemptQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class QuizAttemptSubmissionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_submission_scores_snapshot_answers_and_passes_at_the_exact_threshold(): void
    {
        $this->freezeTime();
        [$learner, $attempt] = $this->attemptWithAnswers();

        $this->actingAs($learner)
            ->post(route('learner.attempts.submission.store', $attempt), [
                'score' => 999,
                'passed' => false,
                'status' => QuizAttemptStatus::Expired->value,
            ])
            ->assertRedirect(route('learner.attempts.result', $attempt))
            ->assertSessionHas('status', 'Your attempt was submitted successfully.');

        $attempt->refresh();
        $this->assertSame(QuizAttemptStatus::Submitted, $attempt->status);
        $this->assertSame(3, $attempt->score);
        $this->assertTrue($attempt->passed);
        $this->assertSame(now()->toDateTimeString(), $attempt->submitted_at?->toDateTimeString());
    }

    public function test_unanswered_questions_score_zero_and_fail(): void
    {
        $learner = User::factory()->learner()->create();
        $attempt = QuizAttempt::factory()->for($learner, 'learner')->create([
            'max_score' => 4,
            'pass_percentage_snapshot' => 50,
        ]);
        QuizAttemptQuestion::factory()->for($attempt, 'attempt')->create(['points' => 4]);

        app(SubmitQuizAttempt::class)->handle($attempt);

        $attempt->refresh();
        $this->assertSame(0, $attempt->score);
        $this->assertFalse($attempt->passed);
    }

    public function test_late_submission_is_finalized_as_expired_with_saved_answers_scored(): void
    {
        [$learner, $attempt] = $this->attemptWithAnswers();
        $attempt->update(['expires_at' => now()->subSecond()]);

        $this->actingAs($learner)->post(route('learner.attempts.submission.store', $attempt))
            ->assertRedirect(route('learner.attempts.result', $attempt));

        $attempt->refresh();
        $this->assertSame(QuizAttemptStatus::Expired, $attempt->status);
        $this->assertSame(3, $attempt->score);
    }

    public function test_repeated_submission_is_idempotent(): void
    {
        [, $attempt] = $this->attemptWithAnswers();
        $action = app(SubmitQuizAttempt::class);
        $first = $action->handle($attempt);
        $submittedAt = $first->submitted_at?->toDateTimeString();
        $this->travel(5)->minutes();

        $second = $action->handle($attempt);

        $this->assertSame($submittedAt, $second->submitted_at?->toDateTimeString());
        $this->assertSame(3, $second->score);
    }

    public function test_other_learner_cannot_submit_an_attempt(): void
    {
        [, $attempt] = $this->attemptWithAnswers();

        $this->actingAs(User::factory()->learner()->create())
            ->post(route('learner.attempts.submission.store', $attempt))
            ->assertForbidden();

        $this->assertSame(QuizAttemptStatus::InProgress, $attempt->fresh()?->status);
    }

    /** @return array{User, QuizAttempt} */
    private function attemptWithAnswers(): array
    {
        $learner = User::factory()->learner()->create();
        $attempt = QuizAttempt::factory()->for($learner, 'learner')->create([
            'max_score' => 5,
            'pass_percentage_snapshot' => 60,
        ]);
        $correctQuestion = QuizAttemptQuestion::factory()->for($attempt, 'attempt')->create(['points' => 3, 'position' => 1]);
        $correctOption = QuizAttemptOption::factory()->for($correctQuestion, 'question')->create(['is_correct' => true]);
        $wrongQuestion = QuizAttemptQuestion::factory()->for($attempt, 'attempt')->create(['points' => 2, 'position' => 2]);
        $wrongOption = QuizAttemptOption::factory()->for($wrongQuestion, 'question')->create(['is_correct' => false]);
        $this->answer($attempt, $correctQuestion, $correctOption);
        $this->answer($attempt, $wrongQuestion, $wrongOption);

        return [$learner, $attempt];
    }

    private function answer(QuizAttempt $attempt, QuizAttemptQuestion $question, QuizAttemptOption $option): void
    {
        $answer = new QuizAttemptAnswer(['answered_at' => now()]);
        $answer->attempt()->associate($attempt);
        $answer->question()->associate($question);
        $answer->option()->associate($option);
        $answer->save();
    }
}
