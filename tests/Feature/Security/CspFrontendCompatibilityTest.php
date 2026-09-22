<?php

namespace Tests\Feature\Security;

use App\Models\LiveSession;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptOption;
use App\Models\QuizAttemptQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CspFrontendCompatibilityTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_attempt_interface_uses_csp_safe_data_hooks_instead_of_inline_expressions(): void
    {
        $this->withoutVite();
        $learner = User::factory()->learner()->create();
        $attempt = QuizAttempt::factory()->for($learner, 'learner')->create();
        $question = QuizAttemptQuestion::factory()->for($attempt, 'attempt')->create();
        QuizAttemptOption::factory()->for($question, 'question')->create();

        $this->actingAs($learner)->get(route('learner.attempts.show', $attempt))
            ->assertSee('data-quiz-attempt', false)
            ->assertDontSee('x-data=', false)
            ->assertDontSee('@change=', false)
            ->assertDontSee('@click=', false);
    }

    public function test_authoring_and_live_interfaces_use_csp_safe_data_hooks(): void
    {
        $this->withoutVite();
        $educator = User::factory()->educator()->create();
        $quiz = Quiz::factory()->for($educator, 'educator')->create();
        $liveSession = LiveSession::factory()->for($quiz)->for($educator, 'host')->create();

        $this->actingAs($educator)->get(route('educator.quizzes.questions.create', $quiz))
            ->assertSee('data-question-form', false)
            ->assertDontSee('x-data=', false);
        $this->actingAs($educator)->get(route('educator.live-sessions.show', $liveSession))
            ->assertSee('data-live-session-sync', false)
            ->assertDontSee('x-data=', false);
    }
}
