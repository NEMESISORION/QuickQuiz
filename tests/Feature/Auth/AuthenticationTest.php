<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_login_screen_is_available_to_guests(): void
    {
        $this->withoutVite();

        $this->get(route('login'))
            ->assertOk()
            ->assertViewIs('auth.login')
            ->assertSeeText('Continue your work');
    }

    public function test_valid_credentials_authenticate_and_redirect_to_the_intended_page(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'SecurePass1',
        ]);

        $response = $this->post(route('login'), [
            'email' => ' USER@EXAMPLE.COM ',
            'password' => 'SecurePass1',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_do_not_authenticate_the_user(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'SecurePass1',
        ]);

        $this->from(route('login'))->post(route('login'), [
            'email' => 'user@example.com',
            'password' => 'WrongPass1',
        ])->assertRedirect(route('login'))->assertSessionHasErrors(['email']);

        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_repeated_failures(): void
    {
        User::factory()->create(['email' => 'user@example.com']);

        foreach (range(1, 5) as $attempt) {
            $this->post(route('login'), [
                'email' => 'user@example.com',
                'password' => 'WrongPass1',
            ]);
        }

        $this->post(route('login'), [
            'email' => 'user@example.com',
            'password' => 'WrongPass1',
        ])->assertSessionHasErrors(['email'])->assertSessionHas(
            'errors',
            fn ($errors): bool => str_contains($errors->first('email'), 'Too many login attempts. Please try again'),
        );

        $this->assertGuest();
    }

    public function test_guest_is_redirected_from_the_workspace_and_authenticated_user_is_redirected_from_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));

        $user = User::factory()->create();

        $this->actingAs($user)->get(route('login'))->assertRedirect(route('dashboard'));
    }

    public function test_logout_ends_the_authenticated_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('landing'));

        $this->assertGuest();
    }

    public function test_workspace_escapes_the_authenticated_users_name(): void
    {
        $user = User::factory()->create([
            'name' => '<script>alert("unsafe")</script>',
        ]);

        $this->withoutVite();

        $this->actingAs($user)
            ->get(route('learner.dashboard'))
            ->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("unsafe")</script>', false);
    }
}
