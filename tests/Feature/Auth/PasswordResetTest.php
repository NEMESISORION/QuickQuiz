<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset as PasswordResetEvent;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_password_reset_request_screen_is_available(): void
    {
        $this->withoutVite();

        $this->get(route('password.request'))
            ->assertOk()
            ->assertViewIs('auth.forgot-password')
            ->assertSeeText('Find your way back');
    }

    public function test_log_mailer_explains_where_local_reset_links_go(): void
    {
        config()->set('mail.default', 'log');
        $this->withoutVite();

        $this->get(route('password.request'))
            ->assertSee('storage/logs/laravel.log')
            ->assertSeeText('not your inbox.');
    }

    public function test_existing_user_receives_a_password_reset_notification(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'user@example.com']);

        $this->post(route('password.email'), [
            'email' => ' USER@EXAMPLE.COM ',
        ])->assertSessionHas('status', 'If an account matches that email, a reset link is on its way.');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_unknown_email_receives_the_same_neutral_response(): void
    {
        Notification::fake();

        $this->post(route('password.email'), [
            'email' => 'missing@example.com',
        ])->assertSessionHas('status', 'If an account matches that email, a reset link is on its way.');

        Notification::assertNothingSent();
    }

    public function test_valid_token_resets_the_password_and_redirects_to_login(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'user@example.com']);

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo(
            $user,
            ResetPassword::class,
            function (ResetPassword $notification) use ($user): bool {
                Event::fake([PasswordResetEvent::class]);

                $response = $this->post(route('password.update'), [
                    'token' => $notification->token,
                    'email' => $user->email,
                    'password' => 'NewSecurePass2',
                    'password_confirmation' => 'NewSecurePass2',
                ]);

                $response->assertRedirect(route('login'))->assertSessionHas('status');
                $this->assertTrue(Hash::check('NewSecurePass2', $user->fresh()->password));
                Event::assertDispatched(PasswordResetEvent::class);

                return true;
            },
        );
    }

    public function test_invalid_token_does_not_change_the_password(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'SecurePass1',
        ]);

        $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'NewSecurePass2',
            'password_confirmation' => 'NewSecurePass2',
        ])->assertSessionHasErrors(['email']);

        $this->assertTrue(Hash::check('SecurePass1', $user->fresh()->password));
    }
}
