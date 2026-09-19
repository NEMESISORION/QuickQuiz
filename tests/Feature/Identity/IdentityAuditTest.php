<?php

namespace Tests\Feature\Identity;

use App\Enums\IdentityAuditEventType;
use App\Enums\UserRole;
use App\Models\IdentityAuditEvent;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class IdentityAuditTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_registration_records_account_creation_without_credentials(): void
    {
        $this->post(route('register'), [
            'name' => 'Audit User',
            'email' => 'audit@example.com',
            'password' => 'SecurePass1',
            'password_confirmation' => 'SecurePass1',
        ])->assertRedirect(route('dashboard'));

        $event = IdentityAuditEvent::query()
            ->where('event', IdentityAuditEventType::Registered)
            ->sole();
        $this->assertSame(IdentityAuditEventType::Registered, $event->event);
        $this->assertSame('audit@example.com', $event->user?->email);
        $this->assertNull($event->metadata);
        $this->assertStringNotContainsString('SecurePass1', $event->toJson());
    }

    public function test_successful_login_records_the_account_and_request_context(): void
    {
        $user = User::factory()->create([
            'email' => 'audit@example.com',
            'password' => 'SecurePass1',
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->withHeader('User-Agent', 'QuickQuiz Security Test')
            ->post(route('login'), [
                'email' => $user->email,
                'password' => 'SecurePass1',
            ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('identity_audit_events', [
            'user_id' => $user->id,
            'event' => IdentityAuditEventType::LoginSucceeded->value,
            'ip_address' => '203.0.113.10',
            'user_agent' => 'QuickQuiz Security Test',
        ]);
    }

    public function test_failed_login_records_only_a_keyed_email_fingerprint(): void
    {
        User::factory()->create([
            'email' => 'audit@example.com',
            'password' => 'SecurePass1',
        ]);

        $this->post(route('login'), [
            'email' => 'audit@example.com',
            'password' => 'WrongPass1',
        ])->assertSessionHasErrors(['email']);

        $event = IdentityAuditEvent::query()
            ->where('event', IdentityAuditEventType::LoginFailed)
            ->sole();
        $this->assertIsString($event->metadata['email_fingerprint']);
        $this->assertArrayNotHasKey('email', $event->metadata);
        $this->assertArrayNotHasKey('password', $event->metadata);
        $this->assertStringNotContainsString('audit@example.com', $event->toJson());
        $this->assertStringNotContainsString('WrongPass1', $event->toJson());
    }

    public function test_role_selection_records_only_the_selected_role(): void
    {
        $user = User::factory()->withoutRole()->create();

        $this->actingAs($user)->post(route('onboarding.store'), [
            'role' => UserRole::Educator->value,
        ])->assertRedirect(route('educator.dashboard'));

        $event = IdentityAuditEvent::query()
            ->where('event', IdentityAuditEventType::RoleSelected)
            ->sole();
        $this->assertSame(['role' => UserRole::Educator->value], $event->metadata);
    }

    public function test_profile_update_records_change_flags_without_identity_values(): void
    {
        $user = User::factory()->learner()->create([
            'email' => 'before@example.com',
        ]);

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Updated User',
            'email' => 'after@example.com',
        ])->assertRedirect(route('profile.edit'));

        $event = IdentityAuditEvent::query()
            ->where('event', IdentityAuditEventType::ProfileUpdated)
            ->sole();
        $this->assertSame(['email_changed' => true], $event->metadata);
        $this->assertStringNotContainsString('before@example.com', $event->toJson());
        $this->assertStringNotContainsString('after@example.com', $event->toJson());
    }

    public function test_logout_records_the_ended_session(): void
    {
        $user = User::factory()->learner()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('landing'));

        $this->assertDatabaseHas('identity_audit_events', [
            'user_id' => $user->id,
            'event' => IdentityAuditEventType::LoggedOut->value,
        ]);
    }

    public function test_password_reset_event_is_recorded(): void
    {
        $user = User::factory()->create();

        event(new PasswordReset($user));

        $this->assertDatabaseHas('identity_audit_events', [
            'user_id' => $user->id,
            'event' => IdentityAuditEventType::PasswordReset->value,
        ]);
    }

    public function test_email_verification_event_is_recorded(): void
    {
        $user = User::factory()->unverified()->create();

        event(new Verified($user));

        $this->assertDatabaseHas('identity_audit_events', [
            'user_id' => $user->id,
            'event' => IdentityAuditEventType::EmailVerified->value,
        ]);
    }
}
