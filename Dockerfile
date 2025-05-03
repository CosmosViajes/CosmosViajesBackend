FROM php:8.2-fpm

# Instala dependencias y extensiones
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && docker-php-ext-enable pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY . .

# Configuración de Laravel
ENV APP_ENV=production
ENV APP_DEBUG=false

CMD ["/start.sh"]