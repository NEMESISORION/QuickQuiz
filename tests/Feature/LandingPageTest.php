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
            ->assertSeeText('Every screen answers a real assessment need.')
            ->assertSeeText('Preview only')
            ->assertSee('aria-label="Static assessment interface preview"', false)
            ->assertSee('<fieldset class="flex flex-col gap-3" disabled>', false)
            ->assertDontSeeText('v2 foundation')
            ->assertDontSeeText('admin / 1234');
    }
}
