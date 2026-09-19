<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_public_responses_include_browser_security_headers(): void
    {
        $this->withoutVite();

        $this->get(route('landing'))
            ->assertOk()
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin')
            ->assertHeader('Cross-Origin-Resource-Policy', 'same-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), geolocation=(), microphone=()')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeaderMissing('Content-Security-Policy')
            ->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_identity_responses_are_not_cached(): void
    {
        $this->withoutVite();

        $this->get(route('login'))
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private');
    }

    public function test_authenticated_responses_are_not_cached(): void
    {
        $user = User::factory()->learner()->create();
        $this->withoutVite();

        $this->actingAs($user)
            ->get(route('learner.dashboard'))
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private');
    }

    public function test_production_https_response_includes_csp_and_hsts(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');
        $this->withoutVite();

        $this->get(route('landing'))
            ->assertOk()
            ->assertHeader(
                'Content-Security-Policy',
                "default-src 'self'; base-uri 'self'; connect-src 'self'; font-src 'self' data:; form-action 'self'; frame-ancestors 'none'; img-src 'self' data:; object-src 'none'; script-src 'self'; style-src 'self'; upgrade-insecure-requests",
            )
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }
}
