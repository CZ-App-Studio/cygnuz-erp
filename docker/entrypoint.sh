#!/bin/sh
set -e

echo "🚀 Starting Cygnuz ERP Container..."

# Wait for database to be ready
echo "⏳ Waiting for database connection..."
until php artisan db:monitor 2>/dev/null; do
    echo "Database is unavailable - sleeping"
    sleep 5
done

echo "✅ Database is ready!"

# Generate app key if not exists
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    echo "🔑 Generating application key..."
    php artisan key:generate --force
fi

# Run migrations
echo "📦 Running database migrations..."
php artisan migrate --force

# Clear and cache configurations
echo "🔧 Optimizing application..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage link
echo "🔗 Creating storage link..."
php artisan storage:link 2>/dev/null || true

# Set permissions
echo "🔒 Setting permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Seed database if in development mode and not already seeded
if [ "$APP_ENV" = "local" ] || [ "$APP_ENV" = "development" ]; then
    echo "🌱 Checking if database needs seeding..."
    if ! php artisan tinker --execute="return \App\Models\User::count();" | grep -q "0"; then
        echo "Database already seeded, skipping..."
    else
        echo "🌱 Seeding database with demo data..."
        php artisan db:seed --force || true
        php artisan module:seed HRCore --force || true
    fi
fi

# Create log directories
mkdir -p /var/log/php-fpm /var/log/supervisor
touch /var/log/php-fpm/www-error.log

echo "✨ Cygnuz ERP is ready!"
echo "🌐 Access the application at: http://localhost:${APP_PORT:-8000}"

# Execute the main command
exec "$@"