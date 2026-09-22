<?php

namespace Tests\Feature;

use App\Enums\QuizAttemptStatus;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Notifications\AttemptCompleted;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_user_sees_only_owned_notifications_and_untrusted_content_is_escaped(): void
    {
        $this->withoutVite();
        $educator = User::factory()->educator()->create();
        $otherEducator = User::factory()->educator()->create();
        $ownedAttempt = $this->completedAttempt($educator, '<script>Owned learner</script>', 'Owned quiz');
        $otherAttempt = $this->completedAttempt($otherEducator, 'Other learner', 'Private other quiz');
        $educator->notify(new AttemptCompleted($ownedAttempt));
        $otherEducator->notify(new AttemptCompleted($otherAttempt));

        $this->actingAs($educator)->get(route('notifications.index'))
            ->assertSeeText('Attempt completed')
            ->assertSee('&lt;script&gt;Owned learner&lt;/script&gt;', false)
            ->assertDontSee('<script>Owned learner</script>', false)
            ->assertDontSeeText('Private other quiz');
    }

    public function test_mark_all_read_updates_only_authenticated_users_notifications(): void
    {
        $educator = User::factory()->educator()->create();
        $otherEducator = User::factory()->educator()->create();
        $educator->notify(new AttemptCompleted($this->completedAttempt($educator, 'Owned learner', 'Owned quiz')));
        $otherEducator->notify(new AttemptCompleted($this->completedAttempt($otherEducator, 'Other learner', 'Other quiz')));

        $this->actingAs($educator)->post(route('notifications.read.store'))
            ->assertRedirect(route('notifications.index'))
            ->assertSessionHas('status', 'Notifications marked as read.');

        $this->assertSame(0, $educator->unreadNotifications()->count());
        $this->assertSame(1, $otherEducator->unreadNotifications()->count());
    }

    public function test_empty_notification_center_explains_what_appears_next(): void
    {
        $this->withoutVite();
        $user = User::factory()->learner()->create();

        $this->actingAs($user)->get(route('notifications.index'))
            ->assertSeeText('No notifications yet.')
            ->assertSeeText('Assessment completions and earned certificates will appear here');
    }

    public function test_guest_is_redirected_from_notification_center(): void
    {
        $this->get(route('notifications.index'))->assertRedirect(route('login'));
        $this->post(route('notifications.read.store'))->assertRedirect(route('login'));
    }

    private function completedAttempt(User $educator, string $learnerName, string $quizTitle): QuizAttempt
    {
        $quiz = Quiz::factory()->published()->for($educator, 'educator')->create(['title' => $quizTitle]);
        $learner = User::factory()->learner()->create(['name' => $learnerName]);

        return QuizAttempt::factory()->for($quiz)->for($learner, 'learner')->create([
            'status' => QuizAttemptStatus::Submitted,
            'score' => 8,
            'max_score' => 10,
            'passed' => true,
            'submitted_at' => now(),
        ]);
    }
}
