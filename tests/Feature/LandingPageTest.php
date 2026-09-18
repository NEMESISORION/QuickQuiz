<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_landing_page_presents_the_quickquiz_product_foundation(): void
    {
        $this->withoutVite();

        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertViewIs('landing')
            ->assertSeeText('Make every quiz feel clear, fair, and worth taking.')
            ->assertSeeText('One system, two focused workspaces')
            ->assertDontSeeText('admin / 1234');
    }
}
