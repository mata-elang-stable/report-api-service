#!/bin/sh


php artisan migrate --force || exit $?

exec /usr/bin/supervisord -c /etc/supervisord.conf
