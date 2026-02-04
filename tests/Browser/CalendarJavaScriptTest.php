<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CalendarJavaScriptTest extends DuskTestCase
{
    /**
     * Test that JavaScript date validation prevents crashes from malformed iCal data.
     */
    public function test_calendar_handles_malformed_ical_data(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->pause(1000) // Wait for page to load
                ->assertPresent('#calendar')
                ->assertDontSee('RangeError') // Should not see JavaScript errors
                ->assertDontSee('invalid date') // Should not see invalid date errors
                ->assertDontSee('Invalid date in calendar') // Should handle calendar errors gracefully
                ->assertDontSee('Invalid date in event'); // Should handle event errors gracefully
        });
    }

    /**
     * Test that the application loads without JavaScript errors.
     */
    public function test_application_loads_without_errors(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->pause(1000) // Wait for page to load
                ->assertPresent('#calendar')
                ->assertSourceMissing('Uncaught')
                ->assertSourceMissing('TypeError')
                ->assertSourceMissing('ReferenceError');

            // Check that main JavaScript functions are available
            $findFurthestExists = $browser->script('return typeof DateUtils !== "undefined" && typeof DateUtils.findFurthestDate === "function"');
            $this->assertTrue($findFurthestExists[0], 'DateUtils.findFurthestDate should be available');
        });
    }

    /**
     * Test that calendar events render properly even with edge case data.
     */
    public function test_calendar_events_render_gracefully(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->pause(1000) // Wait for page to load
                ->assertPresent('#calendar');

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
     * Test that iCal parsing handles malformed data gracefully.
     */
    public function test_ical_parsing_error_resilience(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->pause(1000) // Wait for page to load
                ->assertPresent('#calendar');

            // Test that date utilities handle invalid dates
            $result = $browser->script('
        if (typeof DateUtils !== "undefined" && typeof DateUtils.findFurthestDate === "function") {
          // Test with malformed event data that could cause crashes
          var testEvents = [
            {end: "invalid-date"},
            {end: null},
            {end: ""},
            {end: "2026-02-03T10:00:00Z"}, // Valid event
            {} // Event without end property
          ];
          
          try {
            var result = DateUtils.findFurthestDate(testEvents);
            return {success: true, result: result};
          } catch (e) {
            return {success: false, error: e.message};
          }
        }
        return {success: false, error: "DateUtils not available"};
      ')[0];

            $this->assertTrue($result['success'], 'findFurthestDate should handle malformed data without crashing');
        });
    }

    /**
     * Test that notification system handles iCal errors properly.
     */
    public function test_ical_error_notifications(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->pause(1000)
                ->assertPresent('#calendar')
                ->pause(250);

            // Test that NotificationUtils is available for error reporting
            $notificationUtils = $browser->script('return typeof NotificationUtils !== "undefined"');
            $this->assertTrue($notificationUtils[0], 'NotificationUtils should be available for error reporting');

            // Test error notification doesn't crash the application
            $browser->script('
        if (typeof NotificationUtils !== "undefined") {
          try {
            NotificationUtils.error("Test iCal error", "Test malformed event");
          } catch (e) {
            console.error("Notification failed:", e);
          }
        }
      ');

            // Application should continue functioning
            $browser->pause(1000);
        });
    }

    /**
     * Test that date utilities handle edge cases properly.
     */
    public function test_date_utilities_handle_edge_cases(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->pause(1000)
                ->assertPresent('#calendar')
                ->pause(250);

            // Test findFurthestDate with invalid dates
            $result = $browser->script('
            if (typeof DateUtils !== "undefined" && typeof DateUtils.findFurthestDate === "function") {
              return DateUtils.findFurthestDate([
                {end: "invalid"},
                {end: "2026-02-03"},
                {end: ""},
                {end: null},
                {end: "2026-12-25"}
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
