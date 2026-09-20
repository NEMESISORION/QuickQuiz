<?php

namespace Tests\Feature;

use App\Enums\QuizAttemptStatus;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptOption;
use App\Models\QuizAttemptQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class QuizAttemptExperienceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_active_attempt_renders_questions_navigation_timer_and_saved_progress_without_answer_keys(): void
    {
        $this->withoutVite();
        $learner = User::factory()->learner()->create();
        $attempt = QuizAttempt::factory()->for($learner, 'learner')->create(['expires_at' => now()->addHour()]);
        $question = QuizAttemptQuestion::factory()->for($attempt, 'attempt')->create([
            'prompt' => '<script>Question?</script>',
        ]);
        $option = QuizAttemptOption::factory()->for($question, 'question')->create([
            'content' => 'Visible answer',
            'is_correct' => true,
        ]);
        $answer = $attempt->answers()->make(['answered_at' => now()]);
        $answer->question()->associate($question);
        $answer->option()->associate($option);
        $answer->save();

        $this->actingAs($learner)->get(route('learner.attempts.show', $attempt))
            ->assertOk()
            ->assertSee('&lt;script&gt;Question?&lt;/script&gt;', false)
            ->assertDontSee('<script>Question?</script>', false)
            ->assertSeeText('Visible answer')
            ->assertSeeText('Time remaining')
            ->assertSeeText('Question map')
            ->assertDontSee('is_correct')
            ->assertViewHas('attempt', fn (QuizAttempt $viewAttempt): bool => $viewAttempt->answers->contains('quiz_attempt_option_id', $option->id));
    }

    public function test_expired_attempt_is_closed_and_redirected_before_questions_render(): void
    {
        $learner = User::factory()->learner()->create();
        $attempt = QuizAttempt::factory()->for($learner, 'learner')->create([
            'expires_at' => now()->subSecond(),
        ]);

        $this->actingAs($learner)
            ->get(route('learner.attempts.show', $attempt))
            ->assertRedirect(route('learner.quizzes.show', $attempt->quiz_id))
            ->assertSessionHasErrors(['quiz' => 'Time has expired for this attempt.']);

        $this->assertSame(QuizAttemptStatus::Expired, $attempt->fresh()?->status);
    }
}
