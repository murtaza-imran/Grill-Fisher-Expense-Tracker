#!/bin/bash
# Update the live dashboard from GitHub. Run on the server:  bash deploy.sh
set -e
cd "$(dirname "$0")"

echo "→ Getting the latest code from GitHub"
git pull --ff-only

echo "→ Installing PHP libraries"
composer install --no-dev --optimize-autoloader --no-interaction

echo "→ Updating the database (your data is kept)"
php artisan migrate --force

echo "→ Refreshing caches"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
chmod -R 775 storage bootstrap/cache

echo "✓ Deployed $(git log -1 --format='%h – %s')"
