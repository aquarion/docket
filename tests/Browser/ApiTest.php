<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ApiTest extends DuskTestCase
{
    /**
     * Test that the calendar API endpoint returns JSON.
     */
    public function test_calendar_api_returns_json(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/all-calendars?end=2026-03-01&version=all')
                ->assertSee('[')
                ->assertDontSee('<!DOCTYPE');
        });
    }

    /**
     * Test that calendar elements are present.
     */
    public function test_calendar_data_fetch_via_js(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertPresent('#calendar')
                ->assertPresent('#datetime')
                ->assertPresent('#nextUp');
        });
    }

    /**
     * Test dynamic CSS endpoint.
     */
    public function test_dynamic_css_endpoint(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/calendars.css')
                ->assertSee('.cal-')
                ->assertDontSee('<!DOCTYPE');
        });
    }

    /**
     * Test dynamic JS endpoint.
     */
    public function test_dynamic_js_endpoint(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/docket.js')
                ->assertSee('calendars')
                ->assertDontSee('<!DOCTYPE');
        });
    }
}
