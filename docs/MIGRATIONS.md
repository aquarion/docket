# Docket Migration Guides

This document consolidates all migration guides for the Docket project.

---

## Table of Contents

1. [Laravel Conversion](#laravel-conversion-migration-guide)
2. [Calendar Configuration](#calendar-configuration-migration-guide)
3. [Google Credentials Storage](#google-credentials-storage-migration)

---

# Laravel Conversion - Migration Guide

## Overview

This workspace has been successfully converted from a custom PHP application to Laravel 12. This document explains the changes and how to work with the new structure.

## What Changed

### Directory Structure

| Old Path | New Path | Purpose |
|----------|----------|---------|
| `htdocs/` | `public/` | Public web root |
| `htdocs/index.php` | `public/index.php` | Laravel entry point |
| `lib/docket.lib.php` | `app/Http/Controllers/` | Business logic moved to controllers |
| `lib/gcal.lib.php` | `app/Services/` | Can be moved to services |
| `templates/*.twig` | `resources/views/*.blade.php` | Twig templates → Blade templates |
| `cache/` | `storage/framework/cache/` | Laravel cache directory |

### New Laravel Directories

- `app/` - Application code (Controllers, Models, Services)
- `config/` - Configuration files
- `database/` - Migrations, seeders, factories
- `routes/` - Route definitions
- `resources/views/` - Blade view templates
- `storage/` - Application storage (logs, cache, uploads)
- `tests/` - PHPUnit tests
- `bootstrap/` - Framework bootstrap files

### File Changes

#### Entry Point
- **Old:** `htdocs/index.php` with manual Twig rendering
- **New:** `public/index.php` bootstraps Laravel, routes handled by `routes/web.php`

#### Configuration
- **Old:** Constants defined in `lib/docket.lib.php`
- **New:** `.env` file and `config/` directory

#### Views
- **Old:** Twig templates in `templates/`
- **New:** Blade templates in `resources/views/`

#### Routing
All routes are now centrally defined in `routes/web.php`:
```php
Route::get('/', [CalendarController::class, 'index']);
Route::get('/calendar', [CalendarController::class, 'show']);
Route::get('/all-calendars', [CalendarController::class, 'all']);
// etc.
```

## How to Use the New Structure

### Starting the Application

```bash
# Development server
php artisan serve

# Visit: http://localhost:8000
```

### Configuration

#### Environment Variables (.env)
```env
APP_NAME=Docket
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# Location for sunrise/sunset calculations
MY_LAT=51.5074
MY_LON=-0.1278

# Google Calendar (if using)
GOOGLE_API_KEY=
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
```

#### Calendar Configuration
The `calendars.inc.php` file remains in the root directory for backward compatibility. It's loaded by the `CalendarController`.

### Working with Routes

Routes are defined in `routes/web.php`. Each route maps to a controller method:

```php
Route::get('/', [CalendarController::class, 'index'])->name('home');
```

### Controllers

The main logic is in `app/Http/Controllers/CalendarController.php`. Each method corresponds to a route:

- `index()` - Main calendar page
- `show()` - Individual calendar
- `all()` - All calendars view
- `css()` - Dynamic CSS generation
- `js()` - Dynamic JavaScript generation
- `icalProxy()` - iCal proxy endpoint
- `token()` - Token handling

### Views (Blade Templates)

Blade templates are in `resources/views/`:

```blade
<!-- Old Twig: {{ variable }} -->
<!-- New Blade: {{ $variable }} -->

<!-- Old Twig: {% if condition %} -->
<!-- New Blade: @if($condition) -->

<!-- Old Twig: {% for item in items %} -->
<!-- New Blade: @foreach($items as $item) -->
```

Example conversion:
```blade
<!-- Twig -->
{% if festival == 'christmas' %}
    <link rel="stylesheet" href="/static/css/christmas.css">
{% endif %}

<!-- Blade -->
@if($festival == 'christmas')
    <link rel="stylesheet" href="/static/css/christmas.css">
@endif
```

### Artisan Commands

```bash
# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# View routes
php artisan route:list

# Generate application key
php artisan key:generate

# Run tests
php artisan test

# Code formatting
./vendor/bin/pint
```

## Migrating Custom Code

### From lib/ files to Services

If you have custom functions in `lib/` files, you can:

1. **Keep the old way** (temporary):
   ```php
   // In controller
   require_once base_path('lib/docket.lib.php');
   ```

2. **Create a Service class** (recommended):
   ```php
   // app/Services/DocketService.php
   namespace App\Services;
   
   class DocketService {
       public function getTheme() {
           // Your logic here
       }
   }
   
   // In controller
   use App\Services\DocketService;
   
   $service = new DocketService();
   $theme = $service->getTheme();
   ```

3. **Use Helper functions**:
   Create `app/Helpers/docket.php` and load it via `composer.json`:
   ```json
   "autoload": {
       "files": ["app/Helpers/docket.php"]
   }
   ```

### Database Integration

If you need database support:

```bash
# Create migration
php artisan make:migration create_calendars_table

# Run migrations
php artisan migrate
```

## Legacy Support

The following old paths still work but should be migrated:

- `htdocs/` - Still exists, but use `public/` for new files
- `lib/` - Still exists, but move logic to `app/Services/` or controllers
- `templates/` - Still exists, but use `resources/views/` for new templates

## Common Tasks

### Adding a New Route

1. Define in `routes/web.php`:
   ```php
   Route::get('/my-route', [CalendarController::class, 'myMethod']);
   ```

2. Add method to controller:
   ```php
   public function myMethod(Request $request) {
       return view('my-view');
   }
   ```

3. Create view in `resources/views/my-view.blade.php`

### Adding Configuration

1. Add to `.env`:
   ```env
   MY_SETTING=value
   ```

2. Access in code:
   ```php
   $value = env('MY_SETTING');
   // or
   $value = config('services.my_setting');
   ```

### Adding Dependencies

```bash
# PHP package
composer require vendor/package

# Development package
composer require --dev vendor/package

# JavaScript package
npm install package
```

## Troubleshooting

### "Class not found" errors
```bash
composer dump-autoload
```

### Cache issues
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### Permission issues
```bash
chmod -R 775 storage bootstrap/cache
```

### Route not found
```bash
php artisan route:list  # Verify route exists
php artisan route:clear  # Clear route cache
```

## Benefits of Laravel

1. **Structure** - Clear organization of code
2. **Artisan** - Command-line tools for common tasks
3. **Blade** - Powerful templating engine
4. **Routing** - Clean URL management
5. **Testing** - Built-in PHPUnit support
6. **Packages** - Access to Laravel ecosystem
7. **Security** - CSRF protection, validation, etc.
8. **Database** - Eloquent ORM, migrations
9. **Caching** - Multiple cache drivers
10. **Logging** - Comprehensive logging system

## Next Steps

1. ✓ Convert existing routes to controllers (Done)
2. ✓ Convert Twig templates to Blade (Basic conversion done)
3. ⚠ Migrate lib/ functions to Services (Pending)
4. ⚠ Fully convert remaining Twig templates (Pending)
5. ⚠ Add tests (Not started)
6. ⚠ Optimize autoloading (Not started)

## Resources

- [Laravel Documentation](https://laravel.com/docs/11.x)
- [Blade Templates](https://laravel.com/docs/11.x/blade)
- [Routing](https://laravel.com/docs/11.x/routing)
- [Artisan Console](https://laravel.com/docs/11.x/artisan)
- [Configuration](https://laravel.com/docs/11.x/configuration)

---

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

# Google Credentials Storage Migration

Google OAuth credentials and tokens have been migrated to use Laravel's Storage facade for better security and consistency.

## What Changed

### Old Location (deprecated)
- Credentials: `etc/credentials.json`
- Tokens: `storage/app/tokens/token_{account}.json`

### New Location
- Credentials: `storage/app/google/credentials.json`
- Tokens: `storage/app/google/tokens/token_{account}.json`

## Migration

### Automatic Migration

Run the migration command to move existing files:

```bash
php artisan google:migrate-credentials
```

This will:
- Copy `etc/credentials.json` to `storage/app/google/`
- Move all tokens from `storage/app/tokens/` to `storage/app/google/tokens/`
- Keep original files intact (you can delete them manually after verification)

### Manual Migration

If you prefer to migrate manually:

```bash
# Create directories
mkdir -p storage/app/google/tokens

# Move credentials
mv etc/credentials.json storage/app/google/

# Move tokens (if any exist in old location)
mv storage/app/tokens/token_*.json storage/app/google/tokens/
```

## Setting Up New Credentials

For all accounts, place credentials at:

```
storage/app/google/credentials.json
```

Then authenticate:
```bash
php artisan google:auth aqcom
```

## Security

All files in `storage/app/google/` are:
- Automatically excluded from version control (.gitignore)
- Protected by Laravel's storage permissions
- Accessed only through Laravel's Storage facade
- Tokens are encrypted using Laravel's Crypt facade

## Configuration

Update `.env` if using a custom credentials path:

```env
# Old (deprecated)
GOOGLE_CREDENTIALS_PATH=/path/to/etc/credentials.json

# New (relative to storage/app/)
GOOGLE_CREDENTIALS_PATH=google/credentials.json
```

## Verification

Check that authentication still works:

```bash
# Test existing account
php artisan google:auth aqcom

# Should show: "✓ Account 'aqcom' already has a valid token."
```

## Benefits

1. **Consistency**: All sensitive data in `storage/app/`
2. **Security**: Better file permissions and encryption
3. **Laravel Convention**: Uses Storage facade throughout
4. **Gitignore**: Automatic exclusion from version control
5. **Testing**: Easier to mock and test with Storage facade

---

**End of consolidated migration guides.**
