<?php

namespace Tests\Feature;

use App\Enums\QuizAttemptStatus;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class EducatorQuizResultTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_owner_sees_scoped_attempts_and_correct_summary_statistics(): void
    {
        $this->withoutVite();
        $educator = User::factory()->educator()->create();
        $quiz = Quiz::factory()->published()->for($educator, 'educator')->create();
        $passedLearner = User::factory()->learner()->create(['name' => '<Passed Learner>']);
        $failedLearner = User::factory()->learner()->create(['name' => 'Failed Learner']);
        $activeLearner = User::factory()->learner()->create(['name' => 'Active Learner']);
        $this->attempt($quiz, $passedLearner, QuizAttemptStatus::Submitted, 8, true);
        $this->attempt($quiz, $failedLearner, QuizAttemptStatus::Submitted, 4, false);
        $this->attempt($quiz, $activeLearner, QuizAttemptStatus::InProgress);
        $otherQuiz = Quiz::factory()->published()->for($educator, 'educator')->create();
        $this->attempt($otherQuiz, User::factory()->learner()->create(['name' => 'Other Quiz Learner']), QuizAttemptStatus::Submitted, 10, true);

        $this->actingAs($educator)->get(route('educator.quizzes.results.index', $quiz))
            ->assertOk()
            ->assertSee('&lt;Passed Learner&gt;', false)
            ->assertSeeText('Failed Learner')
            ->assertSeeText('Active Learner')
            ->assertDontSeeText('Other Quiz Learner')
            ->assertSeeTextInOrder(['Attempts', '3', 'Completed', '2', 'Average score', '60%', 'Pass rate', '50%']);
    }

    public function test_result_filter_is_allow_listed_and_selects_only_matching_attempts(): void
    {
        $this->withoutVite();
        $educator = User::factory()->educator()->create();
        $quiz = Quiz::factory()->published()->for($educator, 'educator')->create();
        $this->attempt($quiz, User::factory()->learner()->create(['name' => 'Passing Person']), QuizAttemptStatus::Submitted, 9, true);
        $this->attempt($quiz, User::factory()->learner()->create(['name' => 'Failing Person']), QuizAttemptStatus::Submitted, 3, false);

        $this->actingAs($educator)
            ->get(route('educator.quizzes.results.index', ['quiz' => $quiz, 'status' => 'passed']))
            ->assertOk()
            ->assertSeeText('Passing Person')
            ->assertDontSeeText('Failing Person');

        $this->actingAs($educator)
            ->get(route('educator.quizzes.results.index', ['quiz' => $quiz, 'status' => 'passed; DROP TABLE users']))
            ->assertRedirect()
            ->assertSessionHasErrors(['status']);
    }

    public function test_other_educator_cannot_view_quiz_results(): void
    {
        $owner = User::factory()->educator()->create();
        $quiz = Quiz::factory()->published()->for($owner, 'educator')->create();

        $this->actingAs(User::factory()->educator()->create())
            ->get(route('educator.quizzes.results.index', $quiz))
            ->assertForbidden();
    }

    private function attempt(
        Quiz $quiz,
        User $learner,
        QuizAttemptStatus $status,
        ?int $score = null,
        ?bool $passed = null,
    ): QuizAttempt {
        return QuizAttempt::factory()->for($quiz)->for($learner, 'learner')->create([
            'status' => $status,
            'score' => $score,
            'max_score' => 10,
            'passed' => $passed,
            'submitted_at' => $status === QuizAttemptStatus::InProgress ? null : now(),
        ]);
    }
}
