#!/bin/sh

#cant be done in the Dockerfile as environment values have not yet been defined
printenv > /etc/cron.d/environment.env

cat /etc/cron.d/environment.env >> /etc/cron.d/worker-cron
cat /etc/cron.d/worker-source >> /etc/cron.d/worker-cron
crontab /etc/cron.d/worker-cron
chmod 0644 /etc/cron.d/worker-cron

/usr/local/nginx/nginx
php-fpm
cron

tail -f /dev/null