<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CalendarSelectorTest extends DuskTestCase
{
  /**
   * Test that calendar selector button exists
   */
  public function test_calendar_selector_button_exists(): void
  {
    $this->browse(function (Browser $browser) {
      $browser->visit('/')
        ->assertPresent('#calendar-selector-btn');
    });
  }

  /**
   * Test that modal opens when button is clicked
   */
  public function test_modal_opens_on_button_click(): void
  {
    $this->browse(function (Browser $browser) {
      $browser->visit('/')
        ->assertPresent('#calendar-selector-modal')
        ->assertAttributeContains('#calendar-selector-modal', 'style', 'display: none')
        ->click('#calendar-selector-btn')
        ->waitFor('#calendar-selector-modal.show', 2)
        ->assertVisible('#calendar-selector-modal')
        ->assertSeeIn('.modal-header h2', 'Select Calendar Set');
    });
  }

  /**
   * Test that modal displays calendar sets
   */
  public function test_modal_displays_calendar_sets(): void
  {
    $this->browse(function (Browser $browser) {
      $browser->visit('/')
        ->click('#calendar-selector-btn')
        ->waitFor('#calendar-selector-modal.show', 2)
        ->assertPresent('.calendar-set-list')
        ->assertPresent('.calendar-set-item')
        ->assertSeeIn('.calendar-set-list', 'All Calendars');
    });
  }

  /**
   * Test that modal closes when close button is clicked
   */
  public function test_modal_closes_on_close_button(): void
  {
    $this->browse(function (Browser $browser) {
      $browser->visit('/')
        ->click('#calendar-selector-btn')
        ->waitFor('#calendar-selector-modal.show', 2)
        ->assertVisible('#calendar-selector-modal')
        ->click('.modal-close')
        ->pause(350) // Wait for animation
        ->assertAttributeContains('#calendar-selector-modal', 'style', 'display: none');
    });
  }

  /**
   * Test that modal closes when escape key is pressed
   */
  public function test_modal_closes_on_escape_key(): void
  {
    $this->browse(function (Browser $browser) {
      $browser->visit('/')
        ->click('#calendar-selector-btn')
        ->waitFor('#calendar-selector-modal.show', 2)
        ->assertVisible('#calendar-selector-modal')
        ->driver->getKeyboard()->sendKeys(\Facebook\WebDriver\WebDriverKeys::ESCAPE);

      $browser->pause(350) // Wait for animation
        ->assertAttributeContains('#calendar-selector-modal', 'style', 'display: none');
    });
  }

  /**
   * Test that active calendar set is highlighted
   */
  public function test_active_calendar_set_highlighted(): void
  {
    $this->browse(function (Browser $browser) {
      $browser->visit('/?version=all')
        ->click('#calendar-selector-btn')
        ->waitFor('#calendar-selector-modal.show', 2)
        ->assertPresent('.calendar-set-item.active')
        ->assertSeeIn('.calendar-set-item.active', '✓');
    });
  }

  /**
   * Test switching to a different calendar set
   */
  public function test_switching_calendar_sets(): void
  {
    $this->browse(function (Browser $browser) {
      $browser->visit('/?version=all')
        ->click('#calendar-selector-btn')
        ->waitFor('#calendar-selector-modal.show', 2)
        ->assertPresent('.calendar-set-item[data-set-id="work"] a')
        ->visit('/?version=work')
        ->assertQueryStringHas('version', 'work');
    });
  }
}
