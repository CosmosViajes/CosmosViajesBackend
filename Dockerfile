FROM php:8.2-fpm

# Instala Nginx y dependencias
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpq-dev \
    nginx \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && docker-php-ext-install pdo pdo_pgsql

# Copia el proyecto y configura Nginx
WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --optimize-autoloader

# Configuración de Nginx
COPY default.conf /etc/nginx/sites-available/default
RUN ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/  # Crea enlace simbólico

# Script de inicio
COPY start.sh /start.sh
RUN chmod +x /start.sh

# Permisos y carpetas esenciales
RUN mkdir -p storage/framework/{cache,sessions,views} \
    && chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

CMD ["/start.sh"]