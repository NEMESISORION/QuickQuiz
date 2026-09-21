<?php

namespace Tests\Feature;

use App\Enums\QuizAttemptStatus;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LearnerAttemptHistoryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_learner_sees_only_own_history_with_resume_and_result_links(): void
    {
        $this->withoutVite();
        $learner = User::factory()->learner()->create();
        $activeQuiz = Quiz::factory()->published()->create(['title' => '<Active Quiz>']);
        $completedQuiz = Quiz::factory()->published()->create(['title' => 'Completed Quiz']);
        $active = QuizAttempt::factory()->for($activeQuiz)->for($learner, 'learner')->create();
        $completed = QuizAttempt::factory()->for($completedQuiz)->for($learner, 'learner')->create([
            'status' => QuizAttemptStatus::Submitted,
            'score' => 8,
            'passed' => true,
            'submitted_at' => now(),
        ]);
        $otherAttempt = QuizAttempt::factory()->for(User::factory()->learner(), 'learner')->create();

        $this->actingAs($learner)->get(route('learner.attempts.index'))
            ->assertOk()
            ->assertSee('&lt;Active Quiz&gt;', false)
            ->assertSeeText('Completed Quiz')
            ->assertSee(route('learner.attempts.show', $active))
            ->assertSee(route('learner.attempts.result', $completed))
            ->assertDontSeeText($otherAttempt->quiz->title);
    }

    public function test_learner_can_filter_expired_attempts(): void
    {
        $this->withoutVite();
        $learner = User::factory()->learner()->create();
        $expired = QuizAttempt::factory()->for($learner, 'learner')->create([
            'status' => QuizAttemptStatus::Expired,
            'score' => 0,
            'passed' => false,
            'submitted_at' => now(),
        ]);
        $submitted = QuizAttempt::factory()->for($learner, 'learner')->create([
            'status' => QuizAttemptStatus::Submitted,
            'score' => 1,
            'passed' => true,
            'submitted_at' => now(),
        ]);

        $this->actingAs($learner)
            ->get(route('learner.attempts.index', ['status' => 'expired']))
            ->assertOk()
            ->assertSeeText($expired->quiz->title)
            ->assertDontSeeText($submitted->quiz->title);
    }

    public function test_educator_cannot_access_learner_history(): void
    {
        $this->actingAs(User::factory()->educator()->create())
            ->get(route('learner.attempts.index'))
            ->assertForbidden();
    }
}
