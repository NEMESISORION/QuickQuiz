<?php

namespace Tests\Feature\Identity;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('onboarding.create'))->assertRedirect(route('login'));
    }

    public function test_user_without_role_can_view_role_selection(): void
    {
        $user = User::factory()->withoutRole()->create();
        $this->withoutVite();

        $this->actingAs($user)
            ->get(route('onboarding.create'))
            ->assertOk()
            ->assertViewIs('onboarding.create')
            ->assertSeeText('How will you use QuickQuiz?')
            ->assertSeeText('Educator')
            ->assertSeeText('Learner');
    }

    /**
     * @return array<string, array{UserRole, string}>
     */
    public static function roleSelections(): array
    {
        return [
            'educator workspace' => [UserRole::Educator, 'educator.dashboard'],
            'learner workspace' => [UserRole::Learner, 'learner.dashboard'],
        ];
    }

    #[DataProvider('roleSelections')]
    public function test_valid_selection_assigns_role_once_and_opens_matching_workspace(UserRole $role, string $route): void
    {
        $user = User::factory()->withoutRole()->create();

        $response = $this->actingAs($user)->post(route('onboarding.store'), [
            'role' => $role->value,
        ]);

        $response->assertRedirect(route($route));
        $this->assertSame($role, $user->refresh()->role);
        $this->assertNotNull($user->role_selected_at);
    }

    public function test_invalid_role_is_rejected_without_changing_identity(): void
    {
        $user = User::factory()->withoutRole()->create();

        $this->actingAs($user)
            ->from(route('onboarding.create'))
            ->post(route('onboarding.store'), ['role' => 'administrator'])
            ->assertRedirect(route('onboarding.create'))
            ->assertSessionHasErrors(['role']);

        $this->assertNull($user->refresh()->role);
        $this->assertNull($user->role_selected_at);
    }

    public function test_assigned_user_cannot_select_another_role(): void
    {
        $user = User::factory()->educator()->create();

        $this->actingAs($user)
            ->post(route('onboarding.store'), ['role' => UserRole::Learner->value])
            ->assertForbidden();

        $this->assertSame(UserRole::Educator, $user->refresh()->role);
    }

    public function test_assigned_user_is_redirected_from_role_selection_to_their_workspace(): void
    {
        $user = User::factory()->learner()->create();

        $this->actingAs($user)
            ->get(route('onboarding.create'))
            ->assertRedirect(route('learner.dashboard'));
    }
}
