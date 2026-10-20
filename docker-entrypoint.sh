#!/bin/sh
set -e

PORT="${PORT:-10000}"

sed -i "s/Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:.*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

php /var/www/html/artisan migrate --force --no-interaction

exec apache2-foreground
