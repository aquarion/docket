#!/bin/bash

# Docket Laravel Migration Helper Script
# This script helps migrate from the old structure to Laravel

set -e

echo "Docket Laravel Migration Helper"
echo "================================"
echo ""

# Check if running from project root
if [ ! -f "composer.json" ]; then
    echo "Error: Please run this script from the project root directory"
    exit 1
fi

echo "Step 1: Installing dependencies..."
composer install --no-interaction

echo ""
echo "Step 2: Setting up environment..."
if [ ! -f ".env" ]; then
    cp .env.example .env
    echo "✓ Created .env file"
else
    echo "✓ .env file already exists"
fi

echo ""
echo "Step 3: Generating application key..."
php artisan key:generate --force

echo ""
echo "Step 4: Copying static assets..."
mkdir -p public/static
if [ -d "htdocs/static" ]; then
    cp -r htdocs/static/* public/static/
    echo "✓ Static assets copied"
else
    echo "! htdocs/static directory not found, skipping"
fi

echo ""
echo "Step 5: Setting permissions..."
chmod -R 775 storage bootstrap/cache
echo "✓ Permissions set"

echo ""
echo "Step 6: Calendar configuration..."
if [ ! -f "calendars.inc.php" ]; then
    if [ -f "calendars.inc.example.php" ]; then
        echo "! Please copy calendars.inc.example.php to calendars.inc.php"
        echo "  and configure your calendar sources"
    else
        echo "! No calendar configuration found"
    fi
else
    echo "✓ Calendar configuration exists"
fi

echo ""
echo "Step 7: Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
echo "✓ Caches cleared"

echo ""
echo "================================"
echo "Migration Complete!"
echo ""
echo "Next steps:"
echo "1. Configure calendars.inc.php with your calendar sources"
echo "2. Update .env with your location (MY_LAT, MY_LON)"
echo "3. Add Google API credentials if using Google Calendar"
echo "4. Start the development server: php artisan serve"
echo ""
echo "The application will be available at http://localhost:8000"
