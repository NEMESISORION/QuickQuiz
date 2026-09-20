<?php

namespace Tests\Feature;

use App\Enums\QuizAttemptStatus;
use App\Models\AnswerOption;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LearnerQuizAccessTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_catalogue_shows_released_and_upcoming_quizzes_but_hides_drafts_and_closed_quizzes(): void
    {
        $this->withoutVite();
        $learner = User::factory()->learner()->create();
        $open = Quiz::factory()->published()->create(['title' => '<Open quiz>', 'opens_at' => now()->subMinute()]);
        $upcoming = Quiz::factory()->scheduled()->create(['title' => 'Upcoming quiz']);
        $draft = Quiz::factory()->create(['title' => 'Secret draft']);
        $closed = Quiz::factory()->closed()->create(['title' => 'Closed quiz', 'closes_at' => now()->subMinute()]);

        $this->actingAs($learner)->get(route('learner.quizzes.index'))
            ->assertOk()
            ->assertSee('&lt;Open quiz&gt;', false)
            ->assertSeeText($upcoming->title)
            ->assertDontSeeText($draft->title)
            ->assertDontSeeText($closed->title);
    }

    public function test_educator_cannot_access_the_learner_catalogue(): void
    {
        $this->actingAs(User::factory()->educator()->create())
            ->get(route('learner.quizzes.index'))
            ->assertForbidden();
    }

    public function test_learner_can_start_an_open_quiz_and_is_redirected_to_own_attempt(): void
    {
        $learner = User::factory()->learner()->create();
        $quiz = $this->releasedQuiz();

        $response = $this->actingAs($learner)->post(route('learner.quizzes.attempts.store', $quiz));

        $attempt = QuizAttempt::query()->sole();
        $response->assertRedirect(route('learner.attempts.show', $attempt));
        $this->assertTrue($attempt->learner->is($learner));
    }

    public function test_upcoming_quiz_cannot_be_started_early(): void
    {
        $learner = User::factory()->learner()->create();
        $quiz = $this->releasedQuiz(['opens_at' => now()->addHour()]);

        $this->actingAs($learner)
            ->from(route('learner.quizzes.show', $quiz))
            ->post(route('learner.quizzes.attempts.store', $quiz))
            ->assertRedirect(route('learner.quizzes.show', $quiz))
            ->assertSessionHasErrors(['quiz' => 'This quiz is not open for attempts.']);
    }

    public function test_learner_cannot_view_another_learners_attempt(): void
    {
        $owner = User::factory()->learner()->create();
        $attempt = QuizAttempt::factory()->for($owner, 'learner')->create(['status' => QuizAttemptStatus::InProgress]);

        $this->actingAs(User::factory()->learner()->create())
            ->get(route('learner.attempts.show', $attempt))
            ->assertForbidden();
    }

    /** @param array<string, mixed> $attributes */
    private function releasedQuiz(array $attributes = []): Quiz
    {
        $quiz = Quiz::factory()->published()->create(['opens_at' => now()->subMinute(), ...$attributes]);
        $question = Question::factory()->for($quiz)->create();
        AnswerOption::factory()->correct()->for($question)->create(['position' => 1]);
        AnswerOption::factory()->for($question)->create(['position' => 2]);

        return $quiz;
    }
}
