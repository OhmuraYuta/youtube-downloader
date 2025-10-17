#!/bin/sh
supervisord -c /etc/supervisord.conf
supervisorctl start all
. /usr/local/bin/docker-php-entrypoint php-fpm