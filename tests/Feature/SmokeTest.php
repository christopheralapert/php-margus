<?php

namespace Tests\Feature;

use Tests\TestCase;

class SmokeTest extends TestCase
{
    public function test_root_redirects_to_estonian_home(): void
    {
        $this->get('/')->assertStatus(302)->assertRedirect('/et');
    }

    public function test_estonian_home_is_translated(): void
    {
        $this->get('/et')->assertOk()->assertSee('Järelravi voldik')->assertSee('lang="et"', false);
    }

    public function test_english_home_is_translated(): void
    {
        $this->get('/en')->assertOk()->assertSee('Aftercare leaflet')->assertSee('lang="en"', false);
    }

    public function test_unsupported_locale_is_not_found(): void
    {
        $this->get('/xx')->assertNotFound();
    }
}
