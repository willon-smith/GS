#!/bin/sh
# Bind Apache to the port the host asks for (Render sets PORT, default 10000),
# then hand over to the normal Apache start.
set -e
PORT="${PORT:-80}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
# Make sure the writable folders exist and belong to the web user (fresh volumes start empty).
mkdir -p /var/www/html/data /var/www/html/uploads
chown -R www-data:www-data /var/www/html/data /var/www/html/uploads
exec "$@"
