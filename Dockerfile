FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpq-dev \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && docker-php-ext-install pdo pdo_pgsql

# Copia el proyecto y instala dependencias
WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --optimize-autoloader

# Copia la configuración de Nginx y el script de inicio
COPY default.conf /etc/nginx/sites-available/default
COPY start.sh /start.sh
RUN chmod +x /start.sh

# Da permisos a las carpetas necesarias
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

RUN mkdir -p storage/framework/{cache,sessions,views} \
    && chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

CMD ["/start.sh"]