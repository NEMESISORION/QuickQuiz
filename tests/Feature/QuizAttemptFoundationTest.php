<?php

namespace Tests\Feature;

use App\Domain\Attempts\StartQuizAttempt;
use App\Enums\QuizAttemptStatus;
use App\Enums\QuizReviewPolicy;
use App\Models\AnswerOption;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class QuizAttemptFoundationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_starting_an_attempt_snapshots_questions_options_and_delivery_rules(): void
    {
        $this->travelTo('2026-09-20 12:00:00');
        [$quiz, $question] = $this->releasedQuiz([
            'duration_minutes' => 25,
            'pass_percentage' => 80,
            'closes_at' => now()->addHours(2),
        ]);
        $learner = User::factory()->learner()->create();

        $attempt = app(StartQuizAttempt::class)->handle($quiz, $learner);

        $this->assertSame(QuizAttemptStatus::InProgress, $attempt->status);
        $this->assertSame(1, $attempt->attempt_number);
        $this->assertSame('2026-09-20 12:25:00', $attempt->expires_at?->format('Y-m-d H:i:s'));
        $this->assertSame(80, $attempt->pass_percentage_snapshot);
        $this->assertSame('2026-09-20 14:00:00', $attempt->review_available_at_snapshot?->format('Y-m-d H:i:s'));
        $this->assertSame(3, $attempt->max_score);
        $this->assertSame($question->prompt, $attempt->questions->first()?->prompt);
        $this->assertSame([false, true], $attempt->questions->first()?->options->pluck('is_correct')->all());
    }

    public function test_existing_active_attempt_is_resumed_without_consuming_an_attempt(): void
    {
        [$quiz] = $this->releasedQuiz();
        $learner = User::factory()->learner()->create();
        $action = app(StartQuizAttempt::class);

        $first = $action->handle($quiz, $learner);
        $second = $action->handle($quiz, $learner);

        $this->assertTrue($first->is($second));
        $this->assertSame(1, QuizAttempt::query()->count());
    }

    public function test_attempt_limit_is_enforced_after_a_completed_attempt(): void
    {
        [$quiz] = $this->releasedQuiz(['max_attempts' => 1]);
        $learner = User::factory()->learner()->create();
        QuizAttempt::factory()->for($quiz)->for($learner, 'learner')->create([
            'status' => QuizAttemptStatus::Submitted,
            'submitted_at' => now(),
        ]);

        $this->expectException(ValidationException::class);
        app(StartQuizAttempt::class)->handle($quiz, $learner);
    }

    public function test_quiz_closing_time_caps_the_attempt_expiry(): void
    {
        $this->travelTo('2026-09-20 12:00:00');
        [$quiz] = $this->releasedQuiz(['duration_minutes' => 60, 'closes_at' => now()->addMinutes(10)]);

        $attempt = app(StartQuizAttempt::class)->handle($quiz, User::factory()->learner()->create());

        $this->assertSame('2026-09-20 12:10:00', $attempt->expires_at?->format('Y-m-d H:i:s'));
    }

    public function test_expired_attempt_is_closed_before_a_new_attempt_is_started(): void
    {
        [$quiz] = $this->releasedQuiz(['max_attempts' => 2]);
        $learner = User::factory()->learner()->create();
        $expired = QuizAttempt::factory()->for($quiz)->for($learner, 'learner')->create([
            'expires_at' => now()->subMinute(),
        ]);

        $newAttempt = app(StartQuizAttempt::class)->handle($quiz, $learner);

        $this->assertSame(QuizAttemptStatus::Expired, $expired->fresh()?->status);
        $this->assertSame(2, $newAttempt->attempt_number);
    }

    public function test_snapshot_does_not_change_when_the_source_question_changes(): void
    {
        [$quiz, $question] = $this->releasedQuiz();
        $attempt = app(StartQuizAttempt::class)->handle($quiz, User::factory()->learner()->create());
        $originalPrompt = $attempt->questions->firstOrFail()->prompt;

        $question->update(['prompt' => 'Changed after the attempt started?']);

        $this->assertSame($originalPrompt, $attempt->questions()->firstOrFail()->prompt);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{Quiz, Question}
     */
    private function releasedQuiz(array $attributes = []): array
    {
        $quiz = Quiz::factory()->published()->create([
            'opens_at' => now()->subMinute(),
            'review_policy' => QuizReviewPolicy::AfterClose,
            ...$attributes,
        ]);
        $question = Question::factory()->for($quiz)->create(['prompt' => 'Which answer is correct?', 'points' => 3]);
        AnswerOption::factory()->for($question)->create(['content' => 'Distractor', 'position' => 1]);
        AnswerOption::factory()->correct()->for($question)->create(['content' => 'Correct answer', 'position' => 2]);

        return [$quiz, $question];
    }
}
