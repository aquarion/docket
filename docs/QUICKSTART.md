# Quick Start Guide - Laravel Docket

## ✅ System Ready!

Docket is a Laravel 12 calendar application with seasonal themes and multi-calendar support.

## 🚀 Get Started in 5 Minutes

### Step 1: Install Dependencies

```bash
composer install
npm install
```

### Step 2: Configure Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```env
APP_NAME=Docket
APP_URL=http://localhost:8000

# Location for sunrise/sunset calculations
MY_LAT=51.5074
MY_LON=-0.1278
```

### Step 3: Add Calendars

**Option A: Simple (Legacy)**
```bash
cp calendars.inc.example.php calendars.inc.php
# Edit with your calendar sources
```

**Option B: Modern (Recommended)**
```php
// Edit config/calendars.php - add your calendars
// Add sources to .env: GCAL_WORK_SRC=your-id@gmail.com
```

### Step 4: Set Permissions

```bash
chmod -R 775 storage bootstrap/cache
```

### Step 5: Start Server

**Development Mode:**
```bash
# Terminal 1: Backend
php artisan serve

# Terminal 2: Frontend (Vite)
npm run dev
```

**Or with Docker:**
```bash
sail up
sail npm run dev
```

Visit: **http://localhost:8000**

## 🎨 Test Seasonal Themes

```
http://localhost:8000/?festival=easter
http://localhost:8000/?festival=christmas
```

## 📁 Project Structure

```
app/
  Http/Controllers/CalendarController.php    Main logic
  Services/                                  Business logic
config/
  calendars.php                              Calendars definition
  festivals.php                              Festival config
public/
  static/                                    Assets
resources/
  js/                                        JavaScript source
  views/                                     Blade templates
routes/web.php                              Route definitions
templates/scss/                             Theme styles
```

## 🔧 Essential Commands

**Development**
```bash
php artisan serve              # Start backend server
npm run dev                    # Start Vite dev server
npm run build                  # Build for production
```

**Maintenance**
```bash
php artisan cache:clear       # Clear all caches
php artisan config:clear      # Clear config cache
php artisan route:list        # View all routes
./vendor/bin/pint             # Format code
php artisan test              # Run tests
```

**Debugging**
```bash
php artisan tinker            # Interactive shell
tail -f storage/logs/laravel.log    # View logs
```

## 🎯 Next Steps

1. **[Read Full Documentation](README.md)**
2. **[View Migration Guides](MIGRATIONS.md)**
   - Calendar Configuration
   - Google Credentials Storage
   - Laravel Conversion Details
3. **[Configure Your Calendars](config/calendars.php)**
4. **[Add Festival Themes](config/festivals.php)**

## 🐛 Troubleshooting

**Port Already in Use**
```bash
php artisan serve --port=8001
```

**Permission Denied**
```bash
chmod -R 775 storage bootstrap/cache public/static
```

**Assets Not Loading**
```bash
npm run build
php artisan cache:clear
```

**Calendars Not Showing**
```bash
php artisan config:clear
php artisan cache:clear
```

## 📚 Documentation

- [README](README.md) - Full documentation
- [MIGRATIONS.md](MIGRATIONS.md) - Configuration guides
- [CHANGELOG.md](CHANGELOG.md) - Version history
- [Laravel Docs](https://laravel.com/docs/11.x) - Framework documentation

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
