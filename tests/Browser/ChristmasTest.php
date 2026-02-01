<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ChristmasTest extends DuskTestCase
{
  /**
   * Test that Christmas decorations appear during December.
   */
  public function test_christmas_decorations_in_december(): void
  {
    // Mock current date to December
    $this->travel(new \DateTime('2026-12-15'));

    $this->browse(function (Browser $browser) {
      $browser->visit('/')
        ->waitFor('.christmas-lights', 5)
        ->assertVisible('.christmas-lights');
    });
  }

  /**
   * Test that Christmas CSS is loaded in December.
   */
  public function test_christmas_css_loads_in_december(): void
  {
    $this->travel(new \DateTime('2026-12-20'));

    $this->browse(function (Browser $browser) {
      $browser->visit('/')
        ->assertSourceHas('/static/generated/christmas.css');
    });
  }

  /**
   * Test that Christmas decorations don't appear in other months.
   */
  public function test_no_christmas_decorations_in_summer(): void
  {
    $this->travel(new \DateTime('2026-07-15'));

    $this->browse(function (Browser $browser) {
      $browser->visit('/')
        ->assertMissing('.christmas-lights');
    });
  }
}
