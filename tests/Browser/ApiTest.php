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
      $browser->visit('/api/calendars')
        ->assertSee('{')
        ->assertSee('events');
    });
  }

  /**
   * Test that calendar data can be fetched via JavaScript.
   */
  public function test_calendar_data_fetch_via_js(): void
  {
    $this->browse(function (Browser $browser) {
      $browser->visit('/')
        ->waitFor('#calendar', 5)
        ->script([
          "return fetch('/api/calendars')
                            .then(r => r.json())
                            .then(data => data.events !== undefined)"
        ]);
    });
  }

  /**
   * Test dynamic CSS endpoint.
   */
  public function test_dynamic_css_endpoint(): void
  {
    $this->browse(function (Browser $browser) {
      $browser->visit('/calendars.css')
        ->assertSee('.calendar-')
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
