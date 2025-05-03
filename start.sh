#!/bin/bash

# Salir si hay error
set -e

echo "Iniciando servicios..."

# Iniciar PHP-FPM en background
php-fpm &

# Iniciar Nginx en primer plano (Render espera que el proceso principal esté en primer plano)
nginx -g "daemon off;"
