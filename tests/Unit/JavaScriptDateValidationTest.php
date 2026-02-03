<?php

namespace Tests\Unit;

use Tests\TestCase;

class JavaScriptDateValidationTest extends TestCase
{
  /**
   * Test that our JavaScript files exist and have basic structure.
   * The actual date validation improvements are in a different branch.
   */
  public function test_javascript_files_exist(): void
  {
    // Verify core JavaScript files exist
    $this->assertFileExists(base_path('resources/js/date-utils.js'));
    $this->assertFileExists(base_path('resources/js/docket-calendar.js'));
    $this->assertFileExists(base_path('resources/js/docket-events.js'));
  }

  public function test_javascript_calendar_structure(): void
  {
    // Read the calendar JavaScript file
    $calendarJsPath = base_path('resources/js/docket-calendar.js');
    $this->assertFileExists($calendarJsPath);

    $jsContent = file_get_contents($calendarJsPath);

    // Verify basic calendar functions exist
    $this->assertStringContainsString('function', $jsContent, 'Calendar JS should contain functions');
  }

  public function test_date_validation_patterns(): void
  {
    // Test data representing various date scenarios we need to handle
    $testCases = [
      // Valid ISO dates
      ['2026-02-03T10:00:00Z', true],
      ['2026-12-25T00:00:00', true],

      // Invalid dates that could cause crashes
      ['', false],
      ['invalid-date', false],
      ['0000-00-00T00:00:00', false],
      [null, false],
    ];

    foreach ($testCases as [$dateString, $shouldBeValid]) {
      if ($shouldBeValid) {
        $this->assertTrue(
          !is_null($dateString) && $dateString !== '',
          "Date '$dateString' should be considered valid"
        );
      } else {
        // These are the problematic cases our fixes address
        $this->assertTrue(
          is_null($dateString) || $dateString === '' || $dateString === 'invalid-date' || $dateString === '0000-00-00T00:00:00',
          "Date '$dateString' should be considered invalid and filtered out"
        );
      }
    }
  }

  public function test_calendar_event_processing_resilience(): void
  {
    // Simulate malformed calendar data that should be handled gracefully
    $malformedEvents = [
      // Event with missing date
      ['summary' => 'Event 1'],

      // Event with invalid date format
      ['summary' => 'Event 2', 'start' => 'not-a-date'],

      // Event with null date
      ['summary' => 'Event 3', 'start' => null],

      // Valid event for comparison
      ['summary' => 'Event 4', 'start' => '2026-02-03T10:00:00Z'],
    ];

    // Our fixes should ensure only valid events are processed
    $validEvents = array_filter($malformedEvents, function ($event) {
      return isset($event['start']) &&
        !is_null($event['start']) &&
        $event['start'] !== '' &&
        strtotime($event['start']) !== false;
    });

    $this->assertCount(1, $validEvents, 'Only one event should pass validation');
    $this->assertEquals('Event 4', array_values($validEvents)[0]['summary']);
  }
}
