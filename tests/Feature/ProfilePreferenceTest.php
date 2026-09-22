<?php

namespace Tests\Feature;

use App\Enums\ThemePreference;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ProfilePreferenceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_user_updates_theme_and_notification_preferences_without_changing_identity(): void
    {
        $user = User::factory()->learner()->create(['name' => 'Original learner']);

        $response = $this->actingAs($user)->patch(route('profile.preferences.update'), [
            'theme_preference' => ThemePreference::Dark->value,
            'notifications_enabled' => false,
            'name' => 'Injected name',
            'role' => UserRole::Educator->value,
        ]);

        $response->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'Preferences updated.');
        $user->refresh();
        $this->assertSame(ThemePreference::Dark, $user->theme_preference);
        $this->assertFalse($user->notifications_enabled);
        $this->assertSame('Original learner', $user->name);
        $this->assertSame(UserRole::Learner, $user->role);
    }

    public function test_invalid_theme_is_rejected_without_changing_preferences(): void
    {
        $user = User::factory()->educator()->create();

        $this->actingAs($user)->patch(route('profile.preferences.update'), [
            'theme_preference' => 'midnight-script',
            'notifications_enabled' => true,
        ])->assertSessionHasErrors([
            'theme_preference' => 'The selected theme preference is invalid.',
        ]);

        $this->assertSame(ThemePreference::System, $user->refresh()->theme_preference);
    }

    public function test_workspace_renders_saved_theme_without_client_side_flash(): void
    {
        $this->withoutVite();
        $user = User::factory()->learner()->create(['theme_preference' => ThemePreference::Dark]);

        $this->actingAs($user)->get(route('profile.edit'))
            ->assertSee('<html lang="en" class="dark">', false)
            ->assertSeeText('Color theme')
            ->assertSeeText('In-app notifications');
    }

    public function test_guest_cannot_update_profile_preferences(): void
    {
        $this->patch(route('profile.preferences.update'), [
            'theme_preference' => ThemePreference::Dark->value,
        ])->assertRedirect(route('login'));
    }
}
