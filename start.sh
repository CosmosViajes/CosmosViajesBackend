#!/bin/bash
set -e
php-fpm &
nginx -g "daemon off;"
