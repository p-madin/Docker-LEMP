#!/bin/sh

#cant be done in the Dockerfile as environment values have not yet been defined
printenv > /etc/cron.d/environment.env

docker network create superhost-network 2>/dev/null || true

if [ "$TARGET_ENV" = "live" ] || [ "$TARGET_ENV" = "prod" ]; then
    cp /usr/local/nginx/nginx.live.conf /usr/local/nginx/nginx.conf
fi

cat /etc/cron.d/environment.env >> /etc/cron.d/worker-cron
cat /etc/cron.d/worker-source >> /etc/cron.d/worker-cron
crontab /etc/cron.d/worker-cron
chmod 0644 /etc/cron.d/worker-cron

php /var/www/html/migrate.php

if [ "$DB_VENDOR" = "sqlite" ]; then
    chown -R www-data:www-data /var/sqlite
    chmod -R 775 /var/sqlite
fi

/usr/local/nginx/nginx
php-fpm
cron

tail -f /dev/null