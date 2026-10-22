#!/bin/sh
set -e

PORT="${PORT:-10000}"

sed -i "s/Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:.*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Laravel registra las migraciones aplicadas; solo ejecuta las pendientes.
php /var/www/html/artisan migrate --force --no-interaction
# Ejecuta todos los seeders creando solo los registros faltantes.
php /var/www/html/artisan db:seed --class=ProductionSeeder --force --no-interaction

exec apache2-foreground
