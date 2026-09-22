<?php

namespace Tests\Feature;

use App\Domain\Attempts\SubmitQuizAttempt;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\QuizAttemptOption;
use App\Models\QuizAttemptQuestion;
use App\Models\User;
use App\Notifications\AttemptCompleted;
use App\Notifications\CertificateEarned;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AttemptCompletionNotificationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_passing_submission_notifies_educator_and_learner_exactly_once(): void
    {
        $educator = User::factory()->educator()->create();
        $learner = User::factory()->learner()->create();
        $attempt = $this->passingAttempt($educator, $learner);
        Notification::fake();

        $action = app(SubmitQuizAttempt::class);
        $action->handle($attempt);
        $action->handle($attempt->fresh());

        Notification::assertSentToOnce($educator, AttemptCompleted::class);
        Notification::assertSentToOnce($learner, CertificateEarned::class);
        Notification::assertCount(2);
    }

    public function test_opted_out_users_receive_no_completion_notifications(): void
    {
        $educator = User::factory()->educator()->create(['notifications_enabled' => false]);
        $learner = User::factory()->learner()->create(['notifications_enabled' => false]);
        $attempt = $this->passingAttempt($educator, $learner);
        Notification::fake();

        app(SubmitQuizAttempt::class)->handle($attempt);

        Notification::assertNothingSent();
    }

    private function passingAttempt(User $educator, User $learner): QuizAttempt
    {
        $quiz = Quiz::factory()->published()->for($educator, 'educator')->create([
            'certificates_enabled' => true,
        ]);
        $attempt = QuizAttempt::factory()->for($quiz)->for($learner, 'learner')->create([
            'max_score' => 1,
            'pass_percentage_snapshot' => 100,
        ]);
        $question = QuizAttemptQuestion::factory()->for($attempt, 'attempt')->create(['points' => 1]);
        $option = QuizAttemptOption::factory()->for($question, 'question')->create(['is_correct' => true]);
        $answer = new QuizAttemptAnswer(['answered_at' => now()]);
        $answer->attempt()->associate($attempt);
        $answer->question()->associate($question);
        $answer->option()->associate($option);
        $answer->save();

        return $attempt;
    }
}
