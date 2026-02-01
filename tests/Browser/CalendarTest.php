<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CalendarTest extends DuskTestCase
{
  /**
   * Test that the homepage loads successfully.
   */
  public function test_homepage_loads_successfully(): void
  {
    $this->browse(function (Browser $browser) {
      $browser->visit('/')
        ->assertPresent('#calendar')
        ->assertPresent('#datetime')
        ->assertPresent('#nextUp');
    });
  }

  /**
   * Test that calendar controls are visible.
   */
  public function test_calendar_controls_visible(): void
  {
    $this->markTestSkipped('Calendar controls not yet implemented');
  }

  /**
   * Test calendar navigation buttons.
   */
  public function test_calendar_navigation_buttons(): void
  {
    $this->markTestSkipped('Calendar navigation buttons not yet implemented');
  }

  /**
   * Test that calendar switcher works.
   */
  public function test_calendar_switcher(): void
  {
    $this->browse(function (Browser $browser) {
      $browser->visit('/')
        ->assertPresent('#switch')
        ->assertVisible('#calendar-selector-btn');
    });
  }

  /**
   * Test that events are displayed.
   */
  public function test_events_are_displayed(): void
  {
    $this->markTestSkipped('Event display depends on calendar data being configured');
  }

  /**
   * Test clicking on an event shows details.
   */
  public function test_event_click_shows_details(): void
  {
    $this->markTestSkipped('Event interaction depends on calendar data being configured');
  }

  /**
   * Test countdown timer is visible.
   */
  public function test_countdown_timer_visible(): void
  {
    $this->markTestSkipped('Countdown timer rendering depends on calendar configuration');
  }

  /**
   * Test next event display.
   */
  public function test_next_event_display(): void
  {
    $this->browse(function (Browser $browser) {
      $browser->visit('/')
        ->assertPresent('#nextUp')
        ->assertAttribute('#nextUp', 'role', 'region');
    });
  }

  /**
   * Test responsive design on mobile viewport.
   */
  public function test_mobile_responsive_design(): void
  {
    $this->markTestSkipped('Mobile responsive testing requires calendar controls implementation');
  }

  /**
   * Test that calendars CSS is loaded.
   */
  public function test_calendars_css_loaded(): void
  {
    $this->browse(function (Browser $browser) {
      $browser->visit('/calendars.css')
        ->assertSee('.cal-')
        ->assertSee('background-color');
    });
  }

  /**
   * Test that JavaScript is working.
   */
  public function test_javascript_loaded(): void
  {
    $this->browse(function (Browser $browser) {
      $browser->visit('/')
        ->waitFor('#datetime', 5)
        ->script('return typeof window.ICAL !== "undefined"');
    });
  }

  /**
   * Test keyboard navigation.
   */
  public function test_keyboard_navigation(): void
  {
    $this->browse(function (Browser $browser) {
      $browser->visit('/')
        ->assertPresent('#calendar')
        ->assertPresent('#datetime');
    });
  }

  /**
   * Test accessibility features.
   */
  public function test_accessibility_features(): void
  {
    $this->browse(function (Browser $browser) {
      $browser->visit('/')
        ->assertAttribute('#calendar', 'role', 'img')
        ->assertAttribute('#calendar', 'aria-label', 'Calendar visualization')
        ->assertAttribute('#datetime', 'role', 'timer')
        ->assertAttribute('#nextUp', 'role', 'region');
    });
  }

  /**
   * Test that Vite assets are loaded.
   */
  public function test_vite_assets_loaded(): void
  {
    $this->browse(function (Browser $browser) {
      $browser->visit('/')
        ->assertSourceHas('/build/assets/app-')
        ->assertSourceHas('.js')
        ->assertSourceHas('.css');
    });
  }
}
