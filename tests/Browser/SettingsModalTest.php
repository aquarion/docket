<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class SettingsModalTest extends DuskTestCase
{
    /**
     * Test that settings button exists
     */
    public function test_settings_button_exists(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertPresent('#settings-btn');
        });
    }

    /**
     * Test that settings modal opens when button is clicked
     */
    public function test_settings_modal_opens_on_button_click(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->waitFor('#settings-btn', 5)
                ->assertPresent('#settings-modal')
                ->assertAttributeContains('#settings-modal', 'style', 'display: none')
                ->click('#settings-btn')
                ->waitFor('#settings-modal.show', 5)
                ->assertVisible('#settings-modal')
                ->assertSeeIn('.modal-header h2', 'Settings');
        });
    }

    /**
     * Test that settings modal displays calendar sets section
     */
    public function test_settings_modal_displays_calendar_sets(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->waitFor('#settings-btn', 5)
                ->click('#settings-btn')
                ->waitFor('#settings-modal.show', 5)
                ->assertPresent('.calendar-set-list')
                ->assertPresent('.calendar-set-item')
                ->assertSeeIn('.calendar-set-list', 'All Calendars')
                ->assertSee('Calendar Sets');
        });
    }

    /**
     * Test that settings modal closes when close button is clicked
     */
    public function test_settings_modal_closes_on_close_button(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->waitFor('#settings-btn', 5)
                ->click('#settings-btn')
                ->waitFor('#settings-modal.show', 5)
                ->assertVisible('#settings-modal')
                ->click('.modal-close')
                ->pause(350) // Wait for animation
                ->assertAttributeContains('#settings-modal', 'style', 'display: none');
        });
    }

    /**
     * Test that settings modal closes when escape key is pressed
     */
    public function test_settings_modal_closes_on_escape_key(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->waitFor('#settings-btn', 5)
                ->click('#settings-btn')
                ->waitFor('#settings-modal.show', 5)
                ->assertVisible('#settings-modal')
                ->driver->getKeyboard()->sendKeys(\Facebook\WebDriver\WebDriverKeys::ESCAPE);

            $browser->pause(350) // Wait for animation
                ->assertAttributeContains('#settings-modal', 'style', 'display: none');
        });
    }

    /**
     * Test that active calendar set is highlighted in settings modal
     */
    public function test_active_calendar_set_highlighted(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/?calendar_set=all')
                ->waitFor('#settings-btn', 5)
                ->click('#settings-btn')
                ->waitFor('#settings-modal.show', 5)
                ->assertPresent('.calendar-set-item.active')
                ->assertSeeIn('.calendar-set-item.active', '✓');
        });
    }

    /**
     * Test switching to a different calendar set via settings modal
     */
    public function test_switching_calendar_sets_via_settings(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/?calendar_set=all')
                ->waitFor('#settings-btn', 5)
                ->click('#settings-btn')
                ->waitFor('#settings-modal.show', 5)
                ->assertPresent('.calendar-set-item[data-set-id="all"] a')
                ->assertSeeIn('.calendar-set-item[data-set-id="all"]', 'All Calendars')
                ->assertQueryStringHas('calendar_set', 'all');
        });
    }
}
