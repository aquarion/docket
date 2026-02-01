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
    $this->markTestSkipped('Christmas decorations feature not yet implemented');
  }

  /**
   * Test that Christmas CSS is loaded in December.
   */
  public function test_christmas_css_loads_in_december(): void
  {
    $this->markTestSkipped('Christmas CSS feature not yet implemented');
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
