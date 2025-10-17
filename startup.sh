#!/bin/sh
supervisord -c /etc/supervisord.conf
supervisorctl start all