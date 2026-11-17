#!/bin/sh
set -eu

# Runtime folders live on volumes; make sure Apache can write to them.
mkdir -p /var/www/html/runtime/cache /var/www/html/runtime/session /var/www/html/runtime/logs
chown -R www-data:www-data /var/www/html/runtime /var/www/html/public/uploads

exec docker-php-entrypoint apache2-foreground
