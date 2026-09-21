<?php

namespace Tests\Feature;

use App\Enums\QuizAttemptStatus;
use App\Enums\QuizReviewPolicy;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\QuizAttemptOption;
use App\Models\QuizAttemptQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class QuizAttemptResultTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_immediate_review_shows_result_answer_key_and_escaped_explanation(): void
    {
        $this->withoutVite();
        [$learner, $attempt] = $this->completedAttempt(QuizReviewPolicy::Immediately);

        $this->actingAs($learner)->get(route('learner.attempts.result', $attempt))
            ->assertOk()
            ->assertSeeText('60%')
            ->assertSeeText('Passed')
            ->assertSeeText('Correct answer')
            ->assertSee('&lt;script&gt;Study this&lt;/script&gt;', false)
            ->assertDontSee('<script>Study this</script>', false);
    }

    public function test_after_close_review_hides_answer_key_until_quiz_closes(): void
    {
        $this->withoutVite();
        [$learner, $attempt] = $this->completedAttempt(QuizReviewPolicy::AfterClose);
        $attempt->update(['review_available_at_snapshot' => now()->addHour()]);
        $attempt->quiz->update(['closes_at' => now()->subHour()]);

        $this->actingAs($learner)->get(route('learner.attempts.result', $attempt))
            ->assertOk()
            ->assertSeeText('Answer review is locked')
            ->assertDontSeeText('Correct answer')
            ->assertDontSeeText('The correct choice');

        $this->travel(2)->hours();
        $this->actingAs($learner)->get(route('learner.attempts.result', $attempt))
            ->assertOk()
            ->assertSeeText('Correct answer');
    }

    public function test_never_review_policy_keeps_answer_key_hidden_after_close(): void
    {
        $this->withoutVite();
        [$learner, $attempt] = $this->completedAttempt(QuizReviewPolicy::Never);
        $attempt->quiz->update(['closes_at' => now()->subHour()]);

        $this->actingAs($learner)->get(route('learner.attempts.result', $attempt))
            ->assertOk()
            ->assertSeeText('This assessment does not reveal answer keys.')
            ->assertDontSeeText('Correct answer')
            ->assertDontSeeText('The correct choice');
    }

    public function test_other_learner_cannot_view_result(): void
    {
        [, $attempt] = $this->completedAttempt(QuizReviewPolicy::Immediately);

        $this->actingAs(User::factory()->learner()->create())
            ->get(route('learner.attempts.result', $attempt))
            ->assertForbidden();
    }

    public function test_active_attempt_result_route_returns_learner_to_attempt(): void
    {
        $learner = User::factory()->learner()->create();
        $attempt = QuizAttempt::factory()->for($learner, 'learner')->create();

        $this->actingAs($learner)
            ->get(route('learner.attempts.result', $attempt))
            ->assertRedirect(route('learner.attempts.show', $attempt));
    }

    /** @return array{User, QuizAttempt} */
    private function completedAttempt(QuizReviewPolicy $policy): array
    {
        $learner = User::factory()->learner()->create();
        $attempt = QuizAttempt::factory()->for($learner, 'learner')->create([
            'status' => QuizAttemptStatus::Submitted,
            'review_policy_snapshot' => $policy,
            'score' => 3,
            'max_score' => 5,
            'passed' => true,
            'submitted_at' => now(),
        ]);
        $question = QuizAttemptQuestion::factory()->for($attempt, 'attempt')->create([
            'points' => 3,
            'explanation' => '<script>Study this</script>',
        ]);
        $option = QuizAttemptOption::factory()->for($question, 'question')->create([
            'content' => 'The correct choice',
            'is_correct' => true,
        ]);
        $answer = new QuizAttemptAnswer(['answered_at' => now()]);
        $answer->attempt()->associate($attempt);
        $answer->question()->associate($question);
        $answer->option()->associate($option);
        $answer->save();

        return [$learner, $attempt];
    }
}
