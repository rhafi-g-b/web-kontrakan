#!/usr/bin/env bash
set -e

php artisan storage:link --force || true
php artisan migrate --force
php artisan db:seed --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec apache2-foreground