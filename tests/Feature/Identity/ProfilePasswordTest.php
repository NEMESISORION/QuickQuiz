<?php

namespace Tests\Feature\Identity;

use App\Enums\IdentityAuditEventType;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfilePasswordTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_signed_in_user_can_change_password_and_records_security_activity(): void
    {
        $user = User::factory()->learner()->create([
            'password' => 'OldSecurePass1',
            'remember_token' => 'previous-remember-token',
        ]);

        $response = $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'OldSecurePass1',
            'password' => 'NewSecurePass2',
            'password_confirmation' => 'NewSecurePass2',
        ]);

        $response->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'Password changed. Other signed-in sessions will be asked to sign in again.');
        $this->assertTrue(Hash::check('NewSecurePass2', $user->fresh()->password));
        $this->assertNotSame('previous-remember-token', $user->fresh()->remember_token);
        $this->assertDatabaseHas('identity_audit_events', [
            'user_id' => $user->getKey(),
            'event' => IdentityAuditEventType::PasswordChanged->value,
        ]);
        $this->assertDatabaseMissing('identity_audit_events', [
            'user_id' => $user->getKey(),
            'event' => IdentityAuditEventType::PasswordReset->value,
        ]);
        $this->actingAs($user)->get(route('profile.edit'))
            ->assertSee('Change password');
    }

    public function test_wrong_current_password_does_not_change_password(): void
    {
        $user = User::factory()->learner()->create(['password' => 'OldSecurePass1']);

        $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'WrongSecurePass1',
            'password' => 'NewSecurePass2',
            'password_confirmation' => 'NewSecurePass2',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('OldSecurePass1', $user->fresh()->password));
        $this->assertDatabaseMissing('identity_audit_events', [
            'user_id' => $user->getKey(),
            'event' => IdentityAuditEventType::PasswordChanged->value,
        ]);
    }

    public function test_weak_password_is_rejected(): void
    {
        $user = User::factory()->learner()->create(['password' => 'OldSecurePass1']);

        $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'OldSecurePass1',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('OldSecurePass1', $user->fresh()->password));
    }

    public function test_unconfirmed_password_is_rejected(): void
    {
        $user = User::factory()->learner()->create(['password' => 'OldSecurePass1']);

        $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'OldSecurePass1',
            'password' => 'NewSecurePass2',
            'password_confirmation' => 'DifferentSecurePass3',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('OldSecurePass1', $user->fresh()->password));
    }

    public function test_reusing_current_password_is_rejected(): void
    {
        $user = User::factory()->learner()->create(['password' => 'OldSecurePass1']);

        $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'OldSecurePass1',
            'password' => 'OldSecurePass1',
            'password_confirmation' => 'OldSecurePass1',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('OldSecurePass1', $user->fresh()->password));
    }

    public function test_guest_cannot_change_a_password(): void
    {
        $user = User::factory()->learner()->create(['password' => 'OldSecurePass1']);

        $this->put(route('profile.password.update'), [
            'current_password' => 'OldSecurePass1',
            'password' => 'NewSecurePass2',
            'password_confirmation' => 'NewSecurePass2',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('OldSecurePass1', $user->fresh()->password));
    }
}
