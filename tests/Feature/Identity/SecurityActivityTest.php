<?php

namespace Tests\Feature\Identity;

use App\Enums\IdentityAuditEventType;
use App\Models\IdentityAuditEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SecurityActivityTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('profile.security'))->assertRedirect(route('login'));
    }

    public function test_user_sees_only_their_twenty_most_recent_events(): void
    {
        $user = User::factory()->learner()->create();
        $otherUser = User::factory()->learner()->create();
        IdentityAuditEvent::factory()->count(21)->for($user)->create();
        IdentityAuditEvent::factory()->for($otherUser)->create([
            'event' => IdentityAuditEventType::PasswordReset,
        ]);
        $this->withoutVite();

        $this->actingAs($user)
            ->get(route('profile.security'))
            ->assertOk()
            ->assertViewIs('profile.security-activity')
            ->assertViewHas(
                'events',
                fn (Collection $events): bool => $events->count() === 20
                    && $events->every(fn (IdentityAuditEvent $event): bool => $event->user_id === $user->id),
            )
            ->assertSeeText('Recent activity');
    }

    public function test_security_activity_escapes_stored_request_context(): void
    {
        $user = User::factory()->learner()->create();
        IdentityAuditEvent::factory()->for($user)->create([
            'event' => IdentityAuditEventType::LoginSucceeded,
            'ip_address' => '<script>alert("unsafe")</script>',
        ]);
        $this->withoutVite();

        $this->actingAs($user)
            ->get(route('profile.security'))
            ->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("unsafe")</script>', false);
    }
}
