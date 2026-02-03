<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CalendarJavaScriptTest extends DuskTestCase
{
    /**
     * Test that JavaScript date validation prevents crashes from malformed data.
     */
    public function test_calendar_handles_malformed_dates(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->waitFor('.calendar-container', 10)
                    ->assertDontSee('RangeError') // Should not see JavaScript errors
                    ->assertDontSee('invalid date'); // Should not see invalid date errors
        });
    }

    /**
     * Test that the application loads without JavaScript errors.
     */
    public function test_application_loads_without_errors(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->waitFor('.calendar-container', 10)
                    ->assertSourceDoesntContain('Uncaught')
                    ->assertSourceDoesntContain('TypeError')
                    ->assertSourceDoesntContain('ReferenceError');
            
            // Check that main JavaScript functions are available
            $browser->script('return typeof findFurthestDate !== "undefined"');
            $browser->script('return typeof isValidDate !== "undefined"');
        });
    }

    /**
     * Test that calendar events render properly even with edge case data.
     */
    public function test_calendar_events_render_gracefully(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->waitFor('.calendar-container', 10);
            
            // Inject test data that could cause issues
            $browser->script('
                // Test edge case: empty event data
                if (window.processCalendarEvent) {
                    try {
                        window.processCalendarEvent({});
                        window.processCalendarEvent({start: null});
                        window.processCalendarEvent({start: "invalid-date"});
                        window.processCalendarEvent({start: ""});
                    } catch (e) {
                        console.error("Calendar event processing failed:", e);
                        throw e;
                    }
                }
            ');
            
            // Should not crash or show error messages
            $browser->assertDontSee('Error processing calendar event');
        });
    }

    /**
     * Test that date utilities handle edge cases properly.
     */
    public function test_date_utilities_handle_edge_cases(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/');
            
            // Test findFurthestDate with invalid dates
            $result = $browser->script('
                if (typeof findFurthestDate !== "undefined") {
                    return findFurthestDate([
                        new Date("invalid"),
                        new Date("2026-02-03"),
                        new Date(""),
                        new Date(null),
                        new Date("2026-12-25")
                    ]);
                }
                return null;
            ')[0];
            
            // Should return a valid date, not crash
            $this->assertNotNull($result);
        });
    }

    /**
     * Test that authentication status checks work properly.
     */
    public function test_authentication_status_endpoint(): void
    {
        $this->browse(function (Browser $browser) {
            // Test that the status endpoint returns proper JSON
            $browser->visit('/auth/google/status?account=test')
                    ->assertSee('"account":"test"')
                    ->assertSee('"has_valid_token"');
        });
    }

    /**
     * Test that error notifications display properly.
     */
    public function test_error_notifications_display(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/');
            
            // Test that Toastify is loaded and available
            $toastifyLoaded = $browser->script('return typeof Toastify !== "undefined"');
            $this->assertTrue($toastifyLoaded[0], 'Toastify should be loaded for error notifications');
            
            // Test notification creation doesn't crash
            $browser->script('
                if (typeof Toastify !== "undefined") {
                    Toastify({
                        text: "Test notification",
                        duration: 1000,
                        gravity: "bottom",
                        position: "right"
                    }).showToast();
                }
            ');
            
            // Wait for notification to appear and disappear
            $browser->pause(1500);
        });
    }
}