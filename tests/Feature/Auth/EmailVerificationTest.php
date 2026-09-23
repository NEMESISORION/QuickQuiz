<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_registration_sends_verification_notification(): void
    {
        Notification::fake();

        $this->post(route('register'), [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'SecurePass1',
            'password_confirmation' => 'SecurePass1',
        ])->assertRedirect(route('dashboard'));

        $user = User::query()->where('email', 'new@example.com')->firstOrFail();
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_unverified_user_can_view_verification_prompt(): void
    {
        $user = User::factory()->unverified()->create();
        $this->withoutVite();

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertOk()
            ->assertViewIs('auth.verify-email')
            ->assertSeeText('Check your inbox');
    }

    public function test_log_mailer_explains_where_the_local_verification_link_goes(): void
    {
        config()->set('mail.default', 'log');
        $user = User::factory()->unverified()->create();
        $this->withoutVite();

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertSee('storage/logs/laravel.log')
            ->assertSeeText('it will not arrive in your inbox until SMTP is configured.');
    }

    public function test_unverified_user_is_redirected_from_workspace_to_verification_prompt(): void
    {
        $user = User::factory()->unverified()->learner()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_valid_signed_link_verifies_email_and_dispatches_event(): void
    {
        $user = User::factory()->unverified()->create();
        Event::fake([Verified::class]);
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $this->actingAs($user)->get($verificationUrl)
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('status', 'Email verified. Welcome to QuickQuiz.');

        $this->assertTrue($user->refresh()->hasVerifiedEmail());
        Event::assertDispatched(Verified::class);
    }

    public function test_invalid_signature_does_not_verify_email(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('verification.verify', ['id' => $user->id, 'hash' => sha1($user->email)]))
            ->assertForbidden();

        $this->assertFalse($user->refresh()->hasVerifiedEmail());
    }

    public function test_unverified_user_can_request_another_link(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertSessionHas('status', 'verification-link-sent');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_verified_user_is_redirected_from_verification_prompt(): void
    {
        $user = User::factory()->learner()->create();

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertRedirect(route('dashboard'));
    }
}
