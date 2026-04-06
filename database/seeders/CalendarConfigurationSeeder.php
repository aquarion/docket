<?php

namespace Database\Seeders;

use App\Models\CalendarSet;
use App\Models\CalendarSource;
use Illuminate\Database\Seeder;

class CalendarConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        CalendarSet::query()->delete();
        CalendarSource::query()->delete();

        // Get configuration from config files
        $googleCalendars = config('calendars.google_calendars', []);
        $icalCalendars = config('calendars.ical_calendars', []);
        $calendarSets = config('calendars.calendar_sets', []);
        $defaultSetId = config('calendars.default_calendar_set', 'all');

        // Create calendar sources
        $createdSources = [];

        // Create Google calendar sources
        foreach ($googleCalendars as $key => $calendar) {
            $source = CalendarSource::create([
                'key' => $key,
                'name' => $calendar['name'],
                'type' => 'google',
                'src' => $calendar['src'],
                'color' => $calendar['color'],
                'emoji' => $calendar['emoji'] ?? null,
                'user_id' => null, // Global calendar
                'is_active' => true,
            ]);
            $createdSources[$key] = $source;
        }

        // Create iCal calendar sources
        foreach ($icalCalendars as $key => $calendar) {
            $source = CalendarSource::create([
                'key' => $key,
                'name' => $calendar['name'],
                'type' => 'ical',
                'src' => $calendar['src'],
                'color' => $calendar['color'],
                'emoji' => $calendar['emoji'] ?? null,
                'user_id' => null, // Global calendar
                'is_active' => true,
            ]);
            $createdSources[$key] = $source;
        }

        // Create calendar sets
        foreach ($calendarSets as $key => $set) {
            $calendarSet = CalendarSet::create([
                'key' => $key,
                'name' => $set['name'],
                'emoji' => $set['emoji'] ?? null,
                'user_id' => null, // Global set
                'is_default' => $key === $defaultSetId,
                'is_active' => true,
            ]);

            // Attach calendar sources to the set
            $calendarsToAttach = $set['calendars'] ?? [];

            if (in_array('*', $calendarsToAttach)) {
                // Attach all available sources
                $calendarSet->calendarSources()->attach($createdSources);
            } else {
                // Attach specific sources
                $sourcesToAttach = [];
                foreach ($calendarsToAttach as $calendarKey) {
                    if (isset($createdSources[$calendarKey])) {
                        $sourcesToAttach[] = $createdSources[$calendarKey]->id;
                    }
                }
                if (! empty($sourcesToAttach)) {
                    $calendarSet->calendarSources()->attach($sourcesToAttach);
                }
            }
        }

        $this->command->info('Calendar configuration migrated to database successfully.');
        $this->command->info('Created '.count($createdSources).' calendar sources.');
        $this->command->info('Created '.count($calendarSets).' calendar sets.');
    }
}
