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
        ->assertSee('Docket')
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
    $this->browse(function (Browser $browser) {
      $browser->visit('/')
        ->assertPresent('.calendar-controls')
        ->assertPresent('.calendar-selector')
        ->assertVisible('#today-button');
    });
  }

  /**
   * Test calendar navigation buttons.
   */
  public function test_calendar_navigation_buttons(): void
  {
    $this->browse(function (Browser $browser) {
      $browser->visit('/')
        ->assertVisible('#prev-month')
        ->assertVisible('#next-month')
        ->click('#next-month')
        ->pause(500)
        ->click('#prev-month')
        ->pause(500)
        ->click('#today-button')
        ->pause(500);
    });
  }

  /**
   * Test that calendar switcher works.
   */
  public function test_calendar_switcher(): void
  {
    $this->browse(function (Browser $browser) {
      $browser->visit('/')
        ->assertPresent('.calendar-selector')
        ->click('.calendar-selector')
        ->pause(300)
        ->assertVisible('.calendar-option');
    });
  }

  /**
   * Test that events are displayed.
   */
  public function test_events_are_displayed(): void
  {
    $this->browse(function (Browser $browser) {
      $browser->visit('/')
        ->waitFor('.event', 5)
        ->assertPresent('.event')
        ->assertPresent('.event-title');
    });
  }

  /**
   * Test clicking on an event shows details.
   */
  public function test_event_click_shows_details(): void
  {
    $this->browse(function (Browser $browser) {
      $browser->visit('/')
        ->waitFor('.event', 5)
        ->click('.event:first-child')
        ->pause(300)
        ->assertPresent('.event-details');
    });
  }

  /**
   * Test countdown timer is visible.
   */
  public function test_countdown_timer_visible(): void
  {
    $this->browse(function (Browser $browser) {
      $browser->visit('/')
        ->assertPresent('#countdown-canvas')
        ->assertVisible('#countdown-canvas');
    });
  }

  /**
   * Test next event display.
   */
  public function test_next_event_display(): void
  {
    $this->browse(function (Browser $browser) {
      $browser->visit('/')
        ->assertPresent('#nextUp')
        ->waitFor('#nextUp .event-info', 5);
    });
  }

  /**
   * Test responsive design on mobile viewport.
   */
  public function test_mobile_responsive_design(): void
  {
    $this->browse(function (Browser $browser) {
      $browser->resize(375, 667) // iPhone SE size
        ->visit('/')
        ->assertPresent('#calendar')
        ->assertVisible('.calendar-controls');
    });
  }

  /**
   * Test that calendars CSS is loaded.
   */
  public function test_calendars_css_loaded(): void
  {
    $this->browse(function (Browser $browser) {
      $browser->visit('/calendars.css')
        ->assertSee('.calendar-');
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
        ->keys('body', ['{arrow_right}'])
        ->pause(300)
        ->keys('body', ['{arrow_left}'])
        ->pause(300);
    });
  }

  /**
   * Test accessibility features.
   */
  public function test_accessibility_features(): void
  {
    $this->browse(function (Browser $browser) {
      $browser->visit('/')
        ->assertAttribute('#calendar', 'role', 'main')
        ->assertAttribute('#prev-month', 'aria-label', 'Previous month')
        ->assertAttribute('#next-month', 'aria-label', 'Next month')
        ->assertAttribute('#today-button', 'aria-label', 'Today');
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
