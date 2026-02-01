# Changelog - Laravel Conversion

## [2.0.0] - 2026-02-01

### Major Changes - Laravel 11 Conversion

This release represents a complete architectural overhaul, converting the application from a custom PHP structure to Laravel 11.

### Added

#### Core Laravel Structure
- Laravel 11 framework integration
- Standard Laravel directory structure (`app/`, `config/`, `routes/`, `resources/`, `storage/`, `tests/`)
- Artisan command-line tool
- Blade templating engine
- Comprehensive configuration system

#### Application Files
- `app/Http/Controllers/CalendarController.php` - Main application controller
- `app/Http/Controllers/Controller.php` - Base controller
- `app/Console/Kernel.php` - Console command kernel
- `app/Providers/AppServiceProvider.php` - Service provider
- `bootstrap/app.php` - Laravel 11 application bootstrap
- `bootstrap/providers.php` - Service provider registration
- `public/index.php` - Laravel entry point
- `public/.htaccess` - Apache rewrite rules
- `artisan` - Artisan CLI tool

#### Configuration Files
- `config/app.php` - Application configuration
- `config/cache.php` - Cache configuration
- `config/session.php` - Session configuration
- `config/logging.php` - Logging configuration
- `config/filesystems.php` - Filesystem configuration
- `config/view.php` - View configuration
- `config/services.php` - Third-party services (Google Calendar, location)
- `.env.example` - Environment configuration template
- `pint.json` - Laravel Pint (code style) configuration

#### Routing
- `routes/web.php` - Web routes definition
- `routes/api.php` - API routes definition
- `routes/console.php` - Console commands definition

#### Views (Blade Templates)
- `resources/views/index.blade.php` - Main calendar view
- `resources/views/calendar.blade.php` - Single calendar view
- `resources/views/all-calendars.blade.php` - All calendars view
- `resources/views/calendars.css.blade.php` - Dynamic CSS
- `resources/views/docket.js.blade.php` - Dynamic JavaScript

#### Testing
- `tests/TestCase.php` - Base test case
- `tests/Feature/` - Feature tests directory
- `tests/Unit/` - Unit tests directory
- `phpunit.xml` - PHPUnit configuration

#### Storage Structure
- `storage/app/` - Application storage
- `storage/framework/cache/` - Framework cache
- `storage/framework/sessions/` - Session storage
- `storage/framework/views/` - Compiled views
- `storage/logs/` - Application logs

#### Documentation
- `README.md` - Updated with Laravel installation instructions
- `LARAVEL_MIGRATION.md` - Comprehensive migration guide
- `CHANGELOG.md` - This file

#### Scripts
- `bin/laravel-migrate.sh` - Automated migration helper script

### Changed

#### Composer Dependencies
- Updated to Laravel 11.48.0
- Added `laravel/framework` ^11.0
- Added `laravel/tinker` ^2.9
- Updated `google/apiclient` to ^2.18
- Updated `guzzlehttp/guzzle` to ^7.5
- Removed `twig/twig` (replaced with Blade)

#### Development Dependencies
- Added `fakerphp/faker` ^1.23
- Added `laravel/pint` ^1.13
- Added `laravel/sail` ^1.26
- Added `mockery/mockery` ^1.6
- Added `nunomaduro/collision` ^8.0
- Added `phpunit/phpunit` ^11.0

#### Autoloading
- PSR-4 autoloading for `App\` namespace
- PSR-4 autoloading for `Database\Factories\` and `Database\Seeders\`
- PSR-4 autoloading for `Tests\` namespace

#### Application Logic
- Moved from procedural code in `lib/docket.lib.php` to OOP controller methods
- Converted Twig template logic to Blade
- Implemented Laravel routing system
- Implemented Laravel request/response cycle

#### Environment Configuration
- Moved configuration from PHP constants to `.env` file
- Added Laravel-specific environment variables
- Maintained backward compatibility with `MY_LAT`, `MY_LON`

#### Static Assets
- Copied from `htdocs/static/` to `public/static/`
- Updated asset paths in views

#### Git Configuration
- Updated `.gitignore` for Laravel conventions
- Added storage directory `.gitignore` files
- Added Laravel-specific ignore patterns

### Maintained (Backward Compatibility)

#### Legacy Directories
- `htdocs/` - Original directory maintained
- `lib/` - Original library files maintained
- `templates/` - Original Twig templates maintained
- `calendars.inc.php` - Calendar configuration file location

#### Legacy Functionality
- Calendar configuration format
- Google Calendar integration
- iCal proxy functionality
- Theme switching (day/night)
- Festival detection (Christmas theme)
- Git branch detection in debug mode

### Removed

#### Deprecated Dependencies
- `twig/twig` - Replaced with Blade templating
- `laravel/socialite` - Not needed
- `laravel/pail` - Not needed
- `league/oauth1-client` - Not needed

### Migration Path

For upgrading existing installations:

1. Backup current installation
2. Pull latest changes
3. Run `bin/laravel-migrate.sh` or follow manual steps in README.md
4. Update `calendars.inc.php` if needed
5. Configure `.env` file
6. Copy static assets to `public/static/`
7. Test application: `php artisan serve`

### Breaking Changes

- **Entry Point**: Changed from `htdocs/index.php` to `public/index.php`
- **Web Root**: Changed from `htdocs/` to `public/`
- **Templating**: Changed from Twig to Blade (old templates still in `templates/`)
- **Routing**: All routes now managed by Laravel router
- **Autoloading**: Now uses Composer PSR-4 autoloading

### Notes for Developers

- Use `php artisan` for command-line tasks
- Use `./vendor/bin/pint` for code formatting
- Use `php artisan test` to run tests
- Controllers are in `app/Http/Controllers/`
- Views are in `resources/views/`
- Configuration is in `config/` and `.env`
- See `LARAVEL_MIGRATION.md` for detailed migration guide

### Security

- Laravel's CSRF protection enabled
- Environment-based configuration
- Secure session handling
- Input validation support

### Performance

- Opcode caching support
- Route caching: `php artisan route:cache`
- Config caching: `php artisan config:cache`
- View caching: `php artisan view:cache`

### Future Roadmap

- Complete migration of `lib/` functions to Services
- Full Twig to Blade template conversion
- Add comprehensive test coverage
- Database integration for calendar storage
- API authentication for token endpoint
- Queue support for background tasks

---

## Previous Versions

### [1.x] - Before Laravel Conversion

Custom PHP application with:
- Twig templating
- Manual routing in PHP files
- Custom library files in `lib/`
- Direct Google Calendar API integration
- iCal proxy support
