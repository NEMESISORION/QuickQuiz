<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class WorkspaceAuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_user_without_role_is_redirected_to_onboarding(): void
    {
        $user = User::factory()->withoutRole()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('onboarding.create'));
    }

    public function test_dashboard_routes_educator_to_educator_workspace(): void
    {
        $user = User::factory()->educator()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('educator.dashboard'));
    }

    public function test_dashboard_routes_learner_to_learner_workspace(): void
    {
        $user = User::factory()->learner()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('learner.dashboard'));
    }

    public function test_learner_is_forbidden_from_educator_workspace(): void
    {
        $user = User::factory()->learner()->create();

        $this->actingAs($user)
            ->get(route('educator.dashboard'))
            ->assertForbidden();
    }

    public function test_educator_is_forbidden_from_learner_workspace(): void
    {
        $user = User::factory()->educator()->create();

        $this->actingAs($user)
            ->get(route('learner.dashboard'))
            ->assertForbidden();
    }

    public function test_each_role_can_render_its_own_workspace(): void
    {
        $educator = User::factory()->educator()->create();
        $learner = User::factory()->learner()->create();
        $this->withoutVite();

        $this->actingAs($educator)
            ->get(route('educator.dashboard'))
            ->assertOk()
            ->assertSeeText('Build assessment experiences with purpose.');

        $this->actingAs($learner)
            ->get(route('learner.dashboard'))
            ->assertOk()
            ->assertSeeText('Focus on the question in front of you.');
    }
}
