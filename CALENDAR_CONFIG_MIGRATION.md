# Calendar Configuration Migration Guide

## Overview

Calendar configuration has been migrated from the legacy `calendars.inc.php` file to proper Laravel configuration system using `config/calendars.php`.

## What Changed

### Old System (Legacy)
- Configuration in `calendars.inc.php` (PHP include file)
- Direct variable manipulation
- Constants for API keys and location
- Scope pollution issues

### New System (Laravel Config)
- Configuration in `config/calendars.php`
- Environment-based values in `.env`
- Type-safe and testable
- Follows Laravel conventions

## Migration Options

### Option 1: Use New Laravel Config (Recommended)

1. **Configure calendars in `config/calendars.php`:**

   Uncomment and customize the calendar examples, or add your own:

```php
'google_calendars' => [
    'holidays' => [
        'name' => 'Holidays in the UK',
        'src' => env('GCAL_HOLIDAYS_SRC'),
        'color' => '#865A5A',
        'emoji' => '🎉',
    ],
    'work' => [
        'name' => 'Work Calendar',
        'src' => env('GCAL_WORK_SRC'),
        'color' => '#0096ff',
        'emoji' => '💼',
    ],
    'personal' => [
        'name' => 'Personal',
        'src' => env('GCAL_PERSONAL_SRC'),
        'color' => '#8347c6',
        'emoji' => '🏠',
    ],
    // Add unlimited calendars!
],
```

2. **Add calendar IDs to `.env`:**

```env
GCAL_HOLIDAYS_SRC="k6ihf65p5md3okg9fpu4r2q36qk80r7e@import.calendar.google.com"
GCAL_WORK_SRC="your-work-calendar-id@group.calendar.google.com"
GCAL_PERSONAL_SRC="your-personal-calendar-id@gmail.com"
```

3. **Benefits:**
   - **Unlimited calendars** - Add as many as you need
   - Environment-specific configuration
   - No sensitive data in version control
   - Easily testable
   - Type-safe
   - Cacheable with `php artisan config:cache`

### Option 2: Keep Using Legacy Config (Temporary)

For backward compatibility, you can continue using `calendars.inc.php`:

1. **Set in `.env`:**
```env
CALENDAR_USE_LEGACY_CONFIG=true
```

2. **Keep your existing `calendars.inc.php` file**

3. **Migrate at your own pace**

## Configuration Structure

### Adding Multiple Google Calendars

Edit `config/calendars.php` and add as many calendars as you need:

```php
'google_calendars' => [
    'holidays' => [
        'name' => 'Holidays in the UK',
        'src' => env('GCAL_HOLIDAYS_SRC'),
        'color' => '#865A5A',
        'emoji' => '🎉',
    ],
    'work' => [
        'name' => 'Work Calendar',
        'src' => env('GCAL_WORK_SRC'),
        'color' => '#0096ff',
        'emoji' => '💼',
    ],
    'personal' => [
        'name' => 'Personal',
        'src' => env('GCAL_PERSONAL_SRC'),
        'color' => '#8347c6',
        'emoji' => '🏠',
    ],
    'family' => [
        'name' => 'Family Events',
        'src' => env('GCAL_FAMILY_SRC'),
        'color' => '#ff6b6b',
        'emoji' => '👨‍👩‍👧‍👦',
    ],
    // Add as many as you need!
],
```

Then add the calendar IDs to `.env`:

```env
GCAL_HOLIDAYS_SRC="calendar-id@import.calendar.google.com"
GCAL_WORK_SRC="work-id@group.calendar.google.com"
GCAL_PERSONAL_SRC="personal-id@gmail.com"
GCAL_FAMILY_SRC="family-id@group.calendar.google.com"
```

### Adding Multiple iCal Calendars

Edit `config/calendars.php`:

```php
'ical_calendars' => [
    'work_ical' => [
        'name' => 'Work Calendar',
        'src' => env('ICAL_WORK_URL'),
        'color' => '#0096ff',
        'emoji' => '💼',
    ],
    'birthdays' => [
        'name' => 'Birthdays',
        'src' => env('ICAL_BIRTHDAYS_URL'),
        'color' => '#ff69b4',
        'emoji' => '🎂',
    ],
    'deadlines' => [
        'name' => 'Project Deadlines',
        'src' => env('ICAL_DEADLINES_URL'),
        'color' => '#ff4444',
        'emoji' => '⏰',
    ],
    // Add as many as you need!
],
```

Then add the URLs to `.env`:

```env
ICAL_WORK_URL="https://example.com/work-calendar.ics"
ICAL_BIRTHDAYS_URL="https://example.com/birthdays.ics"
ICAL_DEADLINES_URL="https://example.com/deadlines.ics"
```

### Merged Calendars

```php
'merged_calendars' => [
    'id1-id2' => [
        'color' => '#hexcolor', // Color for overlapping events
    ],
],
```

## Environment Variables

### Core Settings

```env
# Location for sunrise/sunset calculations
MY_LAT=51.5074
MY_LON=-0.1278

# Mapbox API Token
MAPBOX_API_TOKEN=your_mapbox_token_here

# Use legacy config file
CALENDAR_USE_LEGACY_CONFIG=false
```

### Calendar Examples

```env
# Google Calendar Sources (add as many as needed)
GCAL_HOLIDAYS_SRC="calendar-id@import.calendar.google.com"
GCAL_WORK_SRC="work-id@group.calendar.google.com"
GCAL_PERSONAL_SRC="personal-id@gmail.com"
GCAL_FAMILY_SRC="family-id@group.calendar.google.com"
GCAL_SPORTS_SRC="sports-id@group.calendar.google.com"
GCAL_MEETINGS_SRC="meetings-id@group.calendar.google.com"

# iCal Calendar URLs (add as many as needed)
ICAL_WORK_URL="https://example.com/work-calendar.ics"
ICAL_BIRTHDAYS_URL="https://example.com/birthdays.ics"
ICAL_DEADLINES_URL="https://example.com/deadlines.ics"
ICAL_TRAVEL_URL="https://example.com/travel.ics"
```

**Note:** Calendar names, colors, and emojis are defined in `config/calendars.php`, not in `.env`. Only sensitive data (calendar IDs and URLs) go in `.env`.

## Code Improvements Made

### Controller Updates

1. **Added return types to all methods**
2. **Fixed indentation (4 spaces, Laravel standard)**
3. **Added input validation**
4. **Improved error handling**
5. **Fixed scope pollution in config loading**
6. **Strict type comparisons**
7. **Proper null handling**

### Before:
```php
private function loadCalendarConfig()
{
    $configFile = base_path('calendars.inc.php');
    if (file_exists($configFile)) {
        include $configFile;  // Scope pollution!
        return [
            'ical_calendars' => $ical_calendars ?? [],
            // ...
        ];
    }
    // ...
}
```

### After:
```php
private function loadCalendarConfig(): array
{
    if (config('calendars.use_legacy_config')) {
        return $this->loadLegacyConfig();
    }
    
    return [
        'ical_calendars' => config('calendars.ical_calendars', []),
        'google_calendars' => config('calendars.google_calendars', []),
        'merged_calendars' => config('calendars.merged_calendars', []),
    ];
}
```

## Testing

Verify the configuration works:

```bash
# Clear config cache
php artisan config:clear

# Test the application
php artisan serve

# Visit http://localhost:8000
```

## Performance

### Config Caching

In production, cache your configuration:

```bash
php artisan config:cache
```

This pre-compiles all config files for optimal performance.

### Advantages Over Legacy

- **Faster:** Cached config is faster than include
- **Type-safe:** Return types prevent errors
- **Testable:** Easy to mock in tests
- **Secure:** Environment variables for sensitive data

## Troubleshooting

### Calendars Not Showing

1. Check `.env` has correct values
2. Run `php artisan config:clear`
3. Verify config with: `php artisan tinker` → `config('calendars')`

### Still Need Legacy Config

Set `CALENDAR_USE_LEGACY_CONFIG=true` in `.env`

### Want Both Systems

You can combine them by merging in `config/calendars.php`:

```php
'google_calendars' => array_merge(
    $this->loadFromLegacy(),
    [
        'new_calendar' => [
            'name' => env('NEW_CAL_NAME'),
            // ...
        ],
    ]
),
```

## Next Steps

1. ✅ Configuration migrated to Laravel config system
2. ⏳ Update your `.env` with calendar sources
3. ⏳ Test the application
4. ⏳ Remove legacy `calendars.inc.php` when ready
5. ⏳ Run `php artisan config:cache` in production

## Rollback

If you need to rollback:

1. Set `CALENDAR_USE_LEGACY_CONFIG=true` in `.env`
2. Ensure `calendars.inc.php` exists
3. Clear config cache: `php artisan config:clear`

---

**The new system is production-ready and recommended for all new installations!** 🚀
