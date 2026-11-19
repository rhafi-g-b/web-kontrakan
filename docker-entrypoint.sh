#!/usr/bin/env bash
set -e

# 1. Sinkronkan port Apache secara dinamis mengikuti variabel PORT dari Render (default 10000)
PORT="${PORT:-10000}"
printf "Listen %s\n" "${PORT}" > /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]\+>/<VirtualHost \*:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

# 2. Batasi worker Apache agar hemat memori di paket Free Render (512 MB RAM)
cat <<EOF > /etc/apache2/mods-enabled/mpm_prefork.conf
<IfModule mpm_prefork_module>
    StartServers             2
    MinSpareServers          1
    MaxSpareServers          3
    MaxRequestWorkers        5
    MaxConnectionsPerChild   100
</IfModule>
EOF

# 3. Startup ringan tanpa seeding berulang
php artisan storage:link --force || true
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec apache2-foreground