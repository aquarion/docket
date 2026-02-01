# Docket - Laravel Calendar Application

A personal calendar dashboard application built with Laravel 11. Features seasonal themes, Google Calendar integration, and responsive design.

## Requirements

- PHP 8.3+
- Composer
- Node.js 20+ (for frontend assets)
- Docker & Docker Compose (optional, for Laravel Sail)

## Quick Start

### 1. Install Dependencies

```bash
composer install
npm install
```

### 2. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Configure Location

Edit `.env` with your location for sunrise/sunset calculations:

```env
MY_LAT=51.5074
MY_LON=-0.1278
```

### 4. Configure Calendars

Copy the calendar configuration template:

```bash
cp calendars.inc.example.php calendars.inc.php
```

Or use the modern Laravel config system (recommended):

- Edit `config/calendars.php` to define your calendars
- Add calendar IDs/URLs to `.env`

For detailed instructions, see [MIGRATIONS.md](MIGRATIONS.md)

### 5. Set Permissions

```bash
chmod -R 775 storage bootstrap/cache public/static
```

### 6. Start Development Server

```bash
# Traditional
php artisan serve

# Or with Docker (Laravel Sail)
sail up
```

Visit: **http://localhost:8000**

## Features

### 📅 Calendar Display
- Multiple calendar support (Google Calendar, iCal)
- Sunrise/sunset calculations
- Time-of-day theming (day/night modes)
- Next upcoming event display

### 🎨 Seasonal Themes
- **Easter:** Animated eggs (Good Friday - Easter Monday)
- **Christmas:** Winter holiday decorations (December)
- Extensible festival system for custom themes
- Debug mode: `?festival=easter|christmas|none` to test themes

### 🔧 Modern Build System
- Vite for JavaScript bundling
- SCSS compilation with automatic hot-reload
- Optimized asset pipeline
- Static asset organization

## Architecture

### Directory Structure

```
app/
  Http/Controllers/    # Main application logic
  Services/           # Business logic (GoogleCalendarService, ThemeService, etc.)
  Models/             # Database models
config/
  calendars.php       # Calendar configuration
  festivals.php       # Festival definitions
resources/
  js/                 # JavaScript source files
  css/                # CSS source files
routes/
  web.php             # Web routes
public/
  static/             # Static assets (images, fonts)
  index.php           # Entry point
templates/
  scss/               # SCSS files for themes
```

### Key Routes

- `/` - Main calendar view
- `/calendar` - Individual calendar
- `/all-calendars` - All calendars overview
- `/docket.js` - Dynamic JavaScript with festival config
- `/calendars.css` - Dynamic CSS styling
- `/icalproxy` - iCal feed proxy
- `/token` - Token management

## Development

### Asset Building

```bash
# Development with hot reload
npm run dev

# Build for production
npm run build

# SCSS compilation
npm run build:sass

# Watch SCSS changes
npm run build:watch
```

### Code Quality

```bash
# Format code
./vendor/bin/pint

# Run tests
php artisan test

# View logs
tail -f storage/logs/laravel.log
```

## Configuration

See [MIGRATIONS.md](MIGRATIONS.md) for detailed configuration guides:
- [Calendar Configuration Migration](MIGRATIONS.md#calendar-configuration-migration-guide)
- [Google Credentials Storage](MIGRATIONS.md#google-credentials-storage-migration)
- [Laravel Conversion Overview](MIGRATIONS.md#laravel-conversion-migration-guide)

## Development

### Artisan Commands

```bash
# Clear caches
php artisan cache:clear
php artisan view:clear
php artisan config:clear

# Code formatting
./vendor/bin/pint

# Run tests
php artisan test
```

## Google Calendar Integration

For Google Calendar integration, set up your API credentials in `.env`:

```
GOOGLE_API_KEY=your_api_key
GOOGLE_CLIENT_ID=your_client_id
GOOGLE_CLIENT_SECRET=your_client_secret
```

## License

BSD-3-Clause

## Author

Nicholas Avenell <nicholas@istic.net>
