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

# The website folder (public_html next to this app) may hold its own copy of public/.
# Copy the fresh CSS, JS, images and manifest there — but keep its own index.php / .htaccess,
# which point at this app.
WEBROOT="$(dirname "$PWD")/public_html"
if [ -d "$WEBROOT" ] && [ "$(cd "$WEBROOT" && pwd -P)" != "$(cd public && pwd -P)" ]; then
  echo "→ Updating website files in $WEBROOT"
  for item in public/* public/.[!.]*; do
    [ -e "$item" ] || continue
    name="$(basename "$item")"
    case "$name" in index.php|.htaccess) continue ;; esac
    cp -rf "$item" "$WEBROOT/"
  done
fi

echo "✓ Deployed $(git log -1 --format='%h – %s')"
