#!/bin/bash
echo "Instalando dependencias..."
composer install --no-dev --optimize-autoloader

echo "Optimizando Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Ejecutando migraciones..."
php artisan migrate --seed --force