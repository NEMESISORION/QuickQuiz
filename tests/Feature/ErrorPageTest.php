<?php

namespace Tests\Feature;

use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    public function test_missing_page_uses_branded_recovery_state(): void
    {
        $this->withoutVite();
        config()->set('app.debug', false);

        $this->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertSeeText('Page not found')
            ->assertSeeText('The page may have moved, expired, or never existed.')
            ->assertSeeText('Go home');
    }
}
