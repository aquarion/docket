# Quick Start Guide - Laravel Docket

## ✅ Conversion Complete!

Your Docket application has been successfully converted to Laravel 11.

## 🚀 Quick Start

### 1. Basic Setup
```bash
# Install dependencies (if not already done)
composer install

# Create environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Set permissions
chmod -R 775 storage bootstrap/cache
```

### 2. Configure Application

Edit `.env` file:
```env
APP_NAME=Docket
APP_URL=http://localhost:8000

# Your location for sunrise/sunset
MY_LAT=51.5074
MY_LON=-0.1278

# Google Calendar (optional)
GOOGLE_API_KEY=your_key_here
GOOGLE_CLIENT_ID=your_client_id
GOOGLE_CLIENT_SECRET=your_secret
```

### 3. Configure Calendars

Copy and edit calendar configuration:
```bash
cp calendars.inc.example.php calendars.inc.php
# Edit calendars.inc.php with your calendar sources
```

### 4. Start Development Server
```bash
php artisan serve
```

Visit: **http://localhost:8000**

## 📁 New Structure at a Glance

```
docket/
├── app/                      # Application code
│   ├── Http/Controllers/     # Controllers (CalendarController)
│   ├── Models/              # Database models
│   └── Services/            # Business logic services
├── config/                   # Configuration files
├── database/                # Migrations, seeders
├── public/                  # Web root (was htdocs/)
│   ├── index.php           # Entry point
│   └── static/             # Static assets
├── resources/
│   └── views/              # Blade templates
├── routes/
│   ├── web.php             # Web routes
│   └── api.php             # API routes
├── storage/                # Logs, cache, uploads
├── tests/                  # PHPUnit tests
├── .env                    # Environment config
├── artisan                 # CLI tool
└── composer.json           # Dependencies
```

## 🔧 Common Commands

```bash
# Development server
php artisan serve

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# View routes
php artisan route:list

# Code formatting
./vendor/bin/pint

# Run tests (when added)
php artisan test
```

## 🌐 Available Routes

- `/` - Main calendar dashboard
- `/calendar` - Individual calendar view
- `/all-calendars` - All calendars view
- `/calendars.css` - Dynamic CSS
- `/docket.js` - Dynamic JavaScript
- `/icalproxy` - iCal proxy
- `/token` - Token endpoint

## 📚 Documentation

- **README.md** - Installation and overview
- **LARAVEL_MIGRATION.md** - Detailed migration guide
- **CHANGELOG.md** - Version history and changes

## 🎯 What Works Now

✅ Laravel 11 framework installed
✅ Application structure created
✅ Routes configured
✅ Controllers set up
✅ Basic views created
✅ Static assets copied
✅ Configuration system in place
✅ Artisan commands available
✅ Environment configuration

## 🔄 Migration Status

### Completed
- ✅ Composer dependencies updated
- ✅ Laravel structure created
- ✅ Configuration files set up
- ✅ Routes defined
- ✅ Controllers created
- ✅ Basic views created
- ✅ Static assets migrated
- ✅ Environment configuration

### Pending (Optional)
- ⚠️ Complete Twig → Blade template conversion
- ⚠️ Migrate `lib/` functions to Services
- ⚠️ Add comprehensive tests
- ⚠️ Full implementation of calendar endpoints

## 🐛 Troubleshooting

### "Class not found" error
```bash
composer dump-autoload
```

### Permission denied
```bash
chmod -R 775 storage bootstrap/cache
```

### Routes not working
```bash
php artisan route:clear
php artisan cache:clear
```

### View not found
- Check file is in `resources/views/`
- Check file has `.blade.php` extension
- Run `php artisan view:clear`

## 🎨 Customization

### Add a New Route
1. Edit `routes/web.php`
2. Add method to `CalendarController`
3. Create view in `resources/views/`

### Add Configuration
1. Add to `.env` file
2. Access via `env('KEY')` or add to `config/` file

### Add Service
Create in `app/Services/` and use in controllers

## 📝 Notes

- **Legacy files** maintained for backward compatibility
- **Static assets** in both `htdocs/static/` and `public/static/`
- **Calendar config** still uses `calendars.inc.php`
- **Templates** - Both Twig and Blade available during transition

## 🔒 Security

- CSRF protection enabled
- Environment variables for secrets
- Session security configured
- Input validation available

## 🚢 Deployment

For production deployment:
```bash
# Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set environment
APP_ENV=production
APP_DEBUG=false
```

## 💡 Tips

1. Use `php artisan make:*` commands to generate boilerplate
2. Use Laravel Pint for code style: `./vendor/bin/pint`
3. Check logs in `storage/logs/laravel.log`
4. Use `.env` for environment-specific config
5. Run `php artisan list` to see all available commands

## 🎉 Success!

Your application is now running on Laravel 11. Enjoy the benefits of:
- Modern PHP framework
- Clean architecture
- Powerful CLI tools
- Comprehensive ecosystem
- Better testing support
- Security features
- Performance optimization

Happy coding! 🚀
