<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticatedSessionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_password_change_invalidates_a_session_using_the_previous_hash(): void
    {
        $this->withoutVite();

        $user = User::factory()->learner()->create([
            'email' => 'session@example.com',
            'password' => 'SecurePass1',
        ]);
        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'SecurePass1',
        ])->assertRedirect(route('dashboard'));
        $this->get(route('learner.dashboard'))->assertOk();

        User::query()->whereKey($user->id)->update([
            'password' => Hash::make('ChangedPass2'),
        ]);
        Auth::forgetGuards();

        $this->get(route('learner.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
