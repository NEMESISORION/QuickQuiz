<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_registration_screen_is_available_to_guests(): void
    {
        $this->withoutVite();

        $this->get(route('register'))
            ->assertOk()
            ->assertViewIs('auth.register')
            ->assertSeeText('Create your workspace')
            ->assertSee('data-password-toggle="password"', false)
            ->assertSee('data-password-toggle="password_confirmation"', false);
    }

    public function test_valid_registration_creates_and_authenticates_the_user(): void
    {
        Event::fake([Registered::class]);

        $response = $this->post(route('register'), [
            'name' => 'Ada Lovelace',
            'email' => '  ADA@EXAMPLE.COM ',
            'password' => 'SecurePass1',
            'password_confirmation' => 'SecurePass1',
            'role' => 'admin',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'role' => null,
        ]);
        Event::assertDispatched(Registered::class);
    }

    public function test_registration_rejects_duplicate_email_and_weak_password(): void
    {
        $this->post(route('register'), [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ])->assertSessionHasErrors(['password']);

        User::factory()->create(['email' => 'same@example.com']);

        $this->post(route('register'), [
            'name' => 'Second User',
            'email' => 'same@example.com',
            'password' => 'SecurePass2',
            'password_confirmation' => 'SecurePass2',
        ])->assertSessionHasErrors(['email']);

        $this->assertDatabaseCount('users', 1);
    }
}
