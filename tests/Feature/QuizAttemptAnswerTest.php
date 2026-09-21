<?php

namespace Tests\Feature;

use App\Enums\QuizAttemptStatus;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\QuizAttemptOption;
use App\Models\QuizAttemptQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class QuizAttemptAnswerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_owner_can_save_and_replace_an_answer_without_creating_duplicates(): void
    {
        $this->freezeTime();
        [$learner, $attempt, $question, $firstOption, $secondOption] = $this->attemptWithQuestion();

        $this->actingAs($learner)
            ->putJson(route('learner.attempts.answers.update', [$attempt, $question]), ['option_id' => $firstOption->id])
            ->assertOk()
            ->assertJsonPath('answered_count', 1)
            ->assertJsonPath('total_questions', 1);
        $this->actingAs($learner)
            ->putJson(route('learner.attempts.answers.update', [$attempt, $question]), ['option_id' => $secondOption->id])
            ->assertOk();

        $this->assertSame(1, QuizAttemptAnswer::query()->count());
        $this->assertDatabaseHas('quiz_attempt_answers', [
            'quiz_attempt_id' => $attempt->id,
            'quiz_attempt_question_id' => $question->id,
            'quiz_attempt_option_id' => $secondOption->id,
        ]);
    }

    public function test_answer_requires_an_option(): void
    {
        [$learner, $attempt, $question] = $this->attemptWithQuestion();

        $this->actingAs($learner)
            ->putJson(route('learner.attempts.answers.update', [$attempt, $question]), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['option_id']);

        $this->assertSame(0, QuizAttemptAnswer::query()->count());
    }

    public function test_option_must_belong_to_the_question(): void
    {
        [$learner, $attempt, $question] = $this->attemptWithQuestion();
        $otherQuestion = QuizAttemptQuestion::factory()->for($attempt, 'attempt')->create(['position' => 2]);
        $otherOption = QuizAttemptOption::factory()->for($otherQuestion, 'question')->create();

        $this->actingAs($learner)
            ->putJson(route('learner.attempts.answers.update', [$attempt, $question]), ['option_id' => $otherOption->id])
            ->assertNotFound();

        $this->assertSame(0, QuizAttemptAnswer::query()->count());
    }

    public function test_question_must_belong_to_the_attempt(): void
    {
        [$learner, $attempt] = $this->attemptWithQuestion();
        [, $otherAttempt, $otherQuestion, $otherOption] = $this->attemptWithQuestion();

        $this->actingAs($learner)
            ->putJson(route('learner.attempts.answers.update', [$attempt, $otherQuestion]), ['option_id' => $otherOption->id])
            ->assertNotFound();

        $this->assertSame(0, $otherAttempt->answers()->count());
    }

    public function test_other_learner_cannot_save_even_with_an_invalid_payload(): void
    {
        [, $attempt, $question] = $this->attemptWithQuestion();

        $this->actingAs(User::factory()->learner()->create())
            ->putJson(route('learner.attempts.answers.update', [$attempt, $question]), [])
            ->assertForbidden();

        $this->assertSame(0, QuizAttemptAnswer::query()->count());
    }

    public function test_expired_attempt_rejects_save_and_persists_expired_status(): void
    {
        [$learner, $attempt, $question, $option] = $this->attemptWithQuestion();
        $attempt->update(['expires_at' => now()->subSecond()]);

        $this->actingAs($learner)
            ->putJson(route('learner.attempts.answers.update', [$attempt, $question]), ['option_id' => $option->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['attempt']);

        $attempt->refresh();
        $this->assertSame(QuizAttemptStatus::Expired, $attempt->status);
        $this->assertSame(0, $attempt->score);
        $this->assertFalse($attempt->passed);
        $this->assertNotNull($attempt->submitted_at);
        $this->assertSame(0, QuizAttemptAnswer::query()->count());
    }

    /** @return array{User, QuizAttempt, QuizAttemptQuestion, QuizAttemptOption, QuizAttemptOption} */
    private function attemptWithQuestion(): array
    {
        $learner = User::factory()->learner()->create();
        $attempt = QuizAttempt::factory()->for($learner, 'learner')->create([
            'expires_at' => now()->addHour(),
        ]);
        $question = QuizAttemptQuestion::factory()->for($attempt, 'attempt')->create();
        $firstOption = QuizAttemptOption::factory()->for($question, 'question')->create(['position' => 1]);
        $secondOption = QuizAttemptOption::factory()->for($question, 'question')->create(['position' => 2]);

        return [$learner, $attempt, $question, $firstOption, $secondOption];
    }
}
