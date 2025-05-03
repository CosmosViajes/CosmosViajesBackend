#!/bin/bash
set -e

# Inicia PHP-FPM
php-fpm &

# Inicia Nginx en primer plano
nginx -g "daemon off;"
