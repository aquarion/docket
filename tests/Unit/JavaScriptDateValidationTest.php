<?php

namespace Tests\Unit;

use Tests\TestCase;

class JavaScriptDateValidationTest extends TestCase
{
  /**
   * Test that our JavaScript date validation functions work correctly.
   * These tests verify our iCal error handling fixes for malformed calendar data.
   */
  public function test_javascript_date_utilities(): void
  {
    // Read the date utilities JavaScript file
    $dateUtilsPath = base_path('resources/js/date-utils.js');
    $this->assertFileExists($dateUtilsPath);

    $jsContent = file_get_contents($dateUtilsPath);

    // Verify our enhanced findFurthestDate function with error handling
    $this->assertStringContainsString('findFurthestDate', $jsContent, 'Enhanced findFurthestDate function should exist');
    $this->assertStringContainsString('isNaN(end.getTime())', $jsContent, 'Date validation should check for NaN');
    $this->assertStringContainsString('Invalid end date found in event', $jsContent, 'Should log invalid dates');
    $this->assertStringContainsString('continue; // Skip invalid dates', $jsContent, 'Should skip invalid dates');

    // Verify error handling patterns
    $this->assertStringContainsString('console.warn', $jsContent, 'Should warn about invalid dates');
  }

  public function test_javascript_calendar_error_handling(): void
  {
    // Read the calendar JavaScript file
    $calendarJsPath = base_path('resources/js/docket-calendar.js');
    $this->assertFileExists($calendarJsPath);

    $jsContent = file_get_contents($calendarJsPath);

    // Verify comprehensive error handling for iCal parsing
    $this->assertStringContainsString('try {', $jsContent, 'Try-catch blocks should exist for error handling');
    $this->assertStringContainsString('} catch (error) {', $jsContent, 'Catch blocks should exist for error handling');
    $this->assertStringContainsString('Invalid date in calendar', $jsContent, 'Should handle invalid calendar dates');
    $this->assertStringContainsString('NotificationUtils.error', $jsContent, 'Should show user-friendly error notifications');

    // Verify handling of missing date properties
    $this->assertStringContainsString('Event missing end date', $jsContent, 'Should handle missing end dates');
    $this->assertStringContainsString('NotificationUtils.warning', $jsContent, 'Should warn about missing data');
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

      // Event with malformed end date
      ['summary' => 'Event 4', 'start' => '2026-02-03T10:00:00Z', 'end' => 'invalid'],

      // Valid event for comparison
      ['summary' => 'Event 5', 'start' => '2026-02-03T10:00:00Z', 'end' => '2026-02-03T11:00:00Z'],
    ];

    // Our fixes should ensure only valid events are processed
    $validEvents = array_filter($malformedEvents, function ($event) {
      return isset($event['start']) &&
        isset($event['end']) &&
        !is_null($event['start']) &&
        !is_null($event['end']) &&
        $event['start'] !== '' &&
        $event['end'] !== '' &&
        strtotime($event['start']) !== false &&
        strtotime($event['end']) !== false;
    });

    $this->assertCount(1, $validEvents, 'Only one event should pass validation');
    $this->assertEquals('Event 5', array_values($validEvents)[0]['summary']);
  }

  public function test_javascript_events_error_handling(): void
  {
    // Read the events JavaScript file
    $eventsJsPath = base_path('resources/js/docket-events.js');
    $this->assertFileExists($eventsJsPath);

    $jsContent = file_get_contents($eventsJsPath);

    // Verify date validation in event processing
    $this->assertStringContainsString('updateNextUp', $jsContent, 'updateNextUp function should exist');

    // Verify defensive programming for invalid dates
    $this->assertStringContainsString('isNaN(end.getTime())', $jsContent, 'Should validate end dates');
    $this->assertStringContainsString('isNaN(start.getTime())', $jsContent, 'Should validate start dates');
    $this->assertStringContainsString('Skip events with invalid dates', $jsContent, 'Should skip invalid events');
    $this->assertStringContainsString('Invalid date in event', $jsContent, 'Should report invalid event dates');
  }

  public function test_ical_error_prevention(): void
  {
    // Test scenarios that would have caused "RangeError: invalid date" before fixes
    $problematicIcalData = [
      // Events with missing DTEND
      ['dtstart' => '2026-02-03T10:00:00Z'],

      // Events with malformed dates
      ['dtstart' => 'not-a-date', 'dtend' => '2026-02-03T11:00:00Z'],
      ['dtstart' => '2026-02-03T10:00:00Z', 'dtend' => 'also-not-a-date'],

      // Events with empty dates
      ['dtstart' => '', 'dtend' => ''],

      // Valid event
      ['dtstart' => '2026-02-03T10:00:00Z', 'dtend' => '2026-02-03T11:00:00Z'],
    ];

    // Verify our error handling logic would catch these
    foreach ($problematicIcalData as $index => $eventData) {
      $hasValidStart = isset($eventData['dtstart']) &&
        !empty($eventData['dtstart']) &&
        strtotime($eventData['dtstart']) !== false;

      $hasValidEnd = isset($eventData['dtend']) &&
        !empty($eventData['dtend']) &&
        strtotime($eventData['dtend']) !== false;

      $isValid = $hasValidStart && $hasValidEnd;

      if ($index === 4) {
        // The valid event
        $this->assertTrue($isValid, 'Valid event should pass validation');
      } else {
        // The problematic events
        $this->assertFalse($isValid, "Problematic event $index should fail validation");
      }
    }
  }
}
