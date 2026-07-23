#!/bin/bash
# ────────────────────────────────────────────────────────
# HealthIntel Production Deployment Script
# Run on your production server after uploading the project
# ────────────────────────────────────────────────────────
set -e

echo "🚀 HealthIntel Deployment Starting..."
echo ""

# 1. Install dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# 2. Set permissions
echo "🔐 Setting file permissions..."
chmod -R 775 storage bootstrap/cache
chmod -R 775 public/build 2>/dev/null || true

# 3. Run migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force --no-interaction

# 4. Cache everything
echo "⚡ Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 5. Create storage link (if not exists)
echo "🔗 Creating storage link..."
php artisan storage:link

# 6. Seed settings (idempotent — won't overwrite existing)
echo "⚙️ Seeding default settings..."
php artisan db:seed --class=Database\\Seeders\\SettingSeeder 2>/dev/null || true

echo ""
echo "✅ Deployment complete!"
echo "   App URL: https://healthintel.app"
echo ""