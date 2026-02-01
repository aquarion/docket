# Docket - Laravel Calendar Application

A personal calendar dashboard application built with Laravel.

## Installation

### Requirements

- PHP >= 8.1
- Composer
- Node.js & npm (for frontend assets)

### Setup Steps

1. **Install Dependencies**
   ```bash
   composer install
   npm install
   ```

2. **Environment Configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Configure Calendar Settings**
   Copy the example calendar configuration:
   ```bash
   cp calendars.inc.example.php calendars.inc.php
   ```
   
   Edit `calendars.inc.php` to add your calendar sources.

4. **Configure Location**
   Update your latitude and longitude in `.env`:
   ```
   MY_LAT=51.5074
   MY_LON=-0.1278
   ```

5. **Set Permissions**
   ```bash
   chmod -R 775 storage bootstrap/cache
   ```

6. **Migrate Static Assets**
   Copy static files from `htdocs/static/` to `public/static/`:
   ```bash
   mkdir -p public/static
   cp -r htdocs/static/* public/static/
   ```

7. **Start Development Server**
   ```bash
   php artisan serve
   ```
   
   Visit http://localhost:8000

## Laravel Conversion Notes

This application has been converted from a custom PHP application to Laravel 11. Key changes:

### Directory Structure

- **Old:** `htdocs/` → **New:** `public/`
- **Old:** `lib/` → **New:** `app/Services/` and controllers
- **Old:** `templates/` (Twig) → **New:** `resources/views/` (Blade)

### Routing

All routes are now defined in `routes/web.php`:
- `/` - Main calendar view
- `/calendar` - Individual calendar view
- `/all-calendars` - All calendars view
- `/calendars.css` - Dynamic CSS
- `/docket.js` - Dynamic JavaScript
- `/icalproxy` - iCal proxy endpoint
- `/token` - Token endpoint

### Configuration

- Calendar configuration: `calendars.inc.php` (root directory)
- Environment variables: `.env` file
- Application config: `config/` directory

### Controllers

Main application logic is in `app/Http/Controllers/CalendarController.php`

### Views

Blade templates are in `resources/views/`:
- `index.blade.php` - Main calendar page
- `calendar.blade.php` - Single calendar view
- `all-calendars.blade.php` - All calendars view
- `calendars.css.blade.php` - Dynamic CSS
- `docket.js.blade.php` - Dynamic JavaScript

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
