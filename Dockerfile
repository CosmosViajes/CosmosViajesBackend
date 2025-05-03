# Usa una imagen base con Nginx + PHP-FPM
FROM richarvey/nginx-php-fpm:latest


RUN apt-get install -y libpq-dev
RUN docker-php-ext-install pdo pdo_pgsql

# Directorio de trabajo
WORKDIR /var/www/html

# Copia todo el proyecto al contenedor
COPY . .

# Permisos para el script de despliegue
RUN chmod +x scripts/00-laravel-deploy.sh

RUN ./scripts/00-laravel-deploy.sh

# Configuración del entorno
ENV SKIP_COMPOSER 1
ENV WEBROOT /var/www/html/public
ENV PHP_ERRORS_STDERR 1
ENV RUN_SCRIPTS 1
ENV REAL_IP_HEADER 1

# Configuración de Laravel
ENV APP_ENV production
ENV APP_DEBUG false
ENV LOG_CHANNEL stderr
ENV COMPOSER_ALLOW_SUPERUSER 1

# Puerto expuesto (necesario para Render)
EXPOSE 80

# Comando de inicio
CMD ["/start.sh"]