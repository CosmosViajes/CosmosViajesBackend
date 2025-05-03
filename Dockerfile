FROM php:8.2-fpm

# Instala Nginx y dependencias de PostgreSQL
RUN apt-get update \
    && apt-get install -y nginx libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Copia tu código al contenedor
WORKDIR /var/www/html
COPY . .

# Copia la configuración de Nginx y el script de inicio
COPY default.conf /etc/nginx/sites-available/default
COPY start.sh /start.sh
RUN chmod +x /start.sh

# Da permisos a las carpetas necesarias
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

CMD ["/start.sh"]