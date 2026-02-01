# Laravel Conversion - Migration Guide

## Overview

This workspace has been successfully converted from a custom PHP application to Laravel 11. This document explains the changes and how to work with the new structure.

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
