<?php

namespace Tests\Feature\Identity;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_user_can_view_profile_settings(): void
    {
        $user = User::factory()->educator()->create();
        $this->withoutVite();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertViewIs('profile.edit')
            ->assertSeeText('Your profile')
            ->assertSeeText('Educator');
    }

    public function test_user_can_update_identity_but_cannot_mass_assign_role(): void
    {
        Notification::fake();
        $user = User::factory()->learner()->create();

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Grace Hopper',
            'email' => ' GRACE@EXAMPLE.COM ',
            'role' => UserRole::Educator->value,
        ]);

        $response
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'Profile updated. Verify your new email address.');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Grace Hopper',
            'email' => 'grace@example.com',
            'role' => UserRole::Learner->value,
            'email_verified_at' => null,
        ]);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_unchanged_email_preserves_verification_time(): void
    {
        $user = User::factory()->learner()->create();
        $verifiedAt = $user->email_verified_at;

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Updated Name',
            'email' => $user->email,
        ])->assertRedirect(route('profile.edit'));

        $this->assertTrue($verifiedAt->equalTo($user->refresh()->email_verified_at));
    }

    public function test_duplicate_email_is_rejected_without_changing_profile(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->learner()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Changed Name',
            'email' => 'taken@example.com',
        ])->assertSessionHasErrors(['email']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);
    }
}
