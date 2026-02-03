# Changelog - Laravel Conversion

## [Unreleased]

### Fixed
- Google OAuth authentication now correctly receives refresh tokens by using modern `setPrompt('consent')` instead of deprecated `setApprovalPrompt('force')`
- Resolved issue where tokens would expire permanently without refresh capability
- Fixed Google Auth command exit codes and improved authentication reliability
- Calendar data now properly flows through all endpoints after successful authentication
- Fixed OAuth token persistence issue caused by double-processing of authorization codes in AuthController
- Enhanced OAuth flow to check for existing valid tokens before attempting code exchange to prevent "code already used" errors
- Laravel Storage now throws exceptions on file operation failures by enabling `'throw' => true` in filesystem configuration to prevent silent failures
- Added comprehensive return value checking for storage operations, JSON encoding/decoding, and cache operations to prevent silent failures
- Fixed JavaScript date parsing errors with comprehensive validation and error handling for malformed calendar events
- Added defensive programming to prevent "RangeError: invalid date" crashes when processing calendar data with missing or invalid date fields
- Fixed `calendar_set` parameter not flowing through to JavaScript configuration (was using URLSearchParams instead of server-side Blade variable)
- Resolved JavaScript function definition order issues in `docket-js.blade.php` (functions now defined before being called)
- Fixed missing closing braces and structural errors in generated JavaScript that caused runtime errors
- Corrected `this` context issues in timer functions by using `var self = this;` pattern
- Verified all JavaScript files are fully ES5 compatible for iOS 12 Safari support
- Fixed variable hoisting issues in `ios12-polyfills.js` and `docket-calendar.js` to comply with Biome code style rules
- Ensured all 17 Vite modules compile correctly with production build process

### Added
- Calendar authentication status endpoint at `/auth/google/check`
- Enhanced calendar error handling with user-friendly error notifications
- Authentication failure detection in GoogleCalendarService with explicit error messages
- Toastify notification system integration for better user feedback
- Improved notification styling with modern toast design and positioning
- Vite development server integration with Laravel Sail
- Dedicated Vite service in docker-compose.yaml for hot module replacement
- Sail development helper script at `bin/sail-dev.sh`
- **Web-based OAuth authentication flow:**
  - Modal authentication interface for browser-based Google authentication
  - Account status checking without requiring CLI access
  - One-click authentication and revoke functionality
  - Proper error handling with user-friendly notifications
- **Comprehensive test suite for OAuth authentication fixes:**
  - Enhanced GoogleAuthServiceTest with modern OAuth prompt verification
  - GoogleCalendarServiceTest for service error handling validation
  - StorageConfigurationTest to verify storage exception throwing
  - JavaScriptDateValidationTest for frontend robustness (future-ready)
  - Enhanced AuthControllerTest for authentication endpoint validation
  - Browser tests for calendar JavaScript functionality

### Changed
- Updated Google OAuth flow to use `setPrompt('consent')` for consistent refresh token generation
- Enhanced authentication logging to show refresh token availability status
- **Converted all JavaScript to ES5 compatibility for iOS 12 Safari support:**
  - Arrow functions converted to regular `function()` declarations
  - Removed use of `const`/`let` in favor of `var`
  - Replaced `.forEach()` with traditional `for` loops
  - Converted `fetch()` API calls to `XMLHttpRequest`
  - Eliminated template literals in favor of string concatenation
  - Fixed optional chaining to use explicit null checks
- Updated Biome configuration to validate for iOS 12 JavaScript compatibility
- Improved copilot instructions with debugging methodology lessons learned

## [2.1.4] - 2026-02-02

### Added
- Default Google account configuration to services config
- Enhanced GoogleCalendarService to use default account settings

### Fixed
- Improved code formatting and configuration handling in AppServiceProvider

## [2.1.3] - 2026-02-02

### Fixed
- Config override loading now correctly looks in `storage/app/config/` instead of old `etc/config/` location
- AppServiceProvider updated to use proper Laravel storage path for config overrides

## [2.1.2] - 2026-02-02

### Changed
- Moved SCSS files from `templates/scss/` to `resources/css/festivals/` for better Laravel structure
- Updated build configuration to use new SCSS location
- Established `storage/app/config/` as the standard location for config overrides
- Added automatic config migration script for `composer update`

### Removed
- Legacy Twig template files (replaced by Blade templates)
- Old `data/` and `etc/` directories
- Empty `bin/` and `cache/` directories

### Fixed
- File organization now follows Laravel conventions
- SCSS compilation works from proper Laravel resource location

## [2.1.1] - 2026-02-02

### Fixed
- Code formatting and consistency improvements
- Minor JavaScript syntax refinements for better iOS 12 compatibility

## [2.1.0] - 2026-02-02

### Added

#### iOS 12 Compatibility
- iOS 12 JavaScript compatibility through comprehensive ES5 conversion
- `resources/js/ios12-polyfills.js` - Polyfills for modern JavaScript features (Array.includes, String.includes, Array.find, Object.assign, fetch, Promise, Element.closest)
- Babel configuration for automatic ES5 transpilation targeting iOS 12
- Vite legacy plugin with iOS 12 browser target for automatic polyfill injection
- iOS 12 compatibility test page at `/ios12-test.html`

### Changed

#### JavaScript Modernization for Legacy Support
- Converted all arrow functions to regular `function()` syntax across all JavaScript files
- Replaced template literals (backticks) with string concatenation for iOS 12 compatibility
- Replaced optional chaining operators (`?.`) with safe property access checks
- Converted `const`/`let` declarations to `var` for broader browser support
- Replaced `for...of` loops with traditional for loops for array/object iteration
- Replaced modern array methods (`.find()`) with traditional loop-based implementations

#### Build System
- Updated `vite.config.js` with `@vitejs/plugin-legacy` for automatic legacy bundle generation
- Added `babel.config.js` with ES5 preset targeting iOS 12 Safari
- Build process now generates both modern and legacy JavaScript bundles automatically
- Added iOS 12 polyfills as first import in main application bundle

### Fixed
- JavaScript runtime errors on iOS 12 Safari due to unsupported ES6+ features
- Calendar functionality now works on legacy mobile browsers
- Event processing and display compatibility with older Safari versions
- Theme switching and festival animations work on iOS 12
- Modal dialogs and notification system compatible with legacy browsers

## [2.1.0] - 2026-02-01

### Changed
- **Upgraded to Laravel 12** - Minimum PHP version updated to 8.2
- Updated all framework dependencies for Laravel 12 compatibility

### Added

#### Easter Festival Theme
- Easter theme with animated egg emoji decorations replacing zeros in timestamps
- Easter egg bouncing animation (0.6s ease-in-out)
- Animated grass decoration at bottom of page with swaying motion
- Festival-specific CSS in `templates/scss/easter.scss`

#### Festival System
- New `config/festivals.php` configuration for managing seasonal themes
- Festival detection based on actual calendar dates using `easter_date()` function
- Easter theme: Good Friday through Easter Monday (4 days)
- Christmas theme: Full month of December
- Debug query parameter support: `?festival=easter|christmas|none`
- Extensible architecture for adding new festivals

#### JavaScript Improvements
- `resources/js/festival-utilities.js` - Festival callback system for DOM transformations
- Festival utilities now included in Vite bundle instead of separate script
- Callback hooks: `afterRenderDateTime`, `afterRenderEvents`

#### Build System Improvements
- Vite configuration now watches and compiles SCSS files automatically
- SCSS compiler integrated into Vite dev server
- Vite output changed to `public/static/build/` with proper structure
- Updated `package.json` build:sass to process all SCSS files in `templates/scss/`
- Build process now auto-compiles all festival themes
- Added GitHub Actions workflow for Vite builds and Selenium/Dusk browser tests

#### Documentation
- Created consolidated `MIGRATIONS.md` with all migration guides
- Includes Laravel conversion, calendar configuration, and Google credentials storage guides
- Organized root directory: moved documentation to `docs/` folder
- Updated README and QUICKSTART with modern setup guides

### Changed

- `app/Services/ThemeService.php` - Now uses festival configuration system
- `vite.config.js` - Added SCSS compilation plugin and output directory configuration
- `index.blade.php` - Festival selector UI for debug mode, Vite bundle now includes festival utilities
- `docket-main.js` - Enhanced initialization to wait for SunCalc library
- `.gitignore` - Added `/public/static/build` for Vite-generated files
- Holiday detection uses proper Easter date calculation instead of hard-coded date range

### Fixed

- Easter eggs no longer display in February (now only during Easter weekend)
- SunCalc "not yet loaded" warnings eliminated
- CSS asset path issues with Vite rebuild

### Developer Experience

- SCSS changes now hot-reload via Vite dev server
- Festival configuration centralized for easy customization
- New festival themes can be added by updating `config/festivals.php`
- Cleaner separation between source files (`resources/`) and built assets

---

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
