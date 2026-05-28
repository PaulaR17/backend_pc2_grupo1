# Backend de EcoTraffic (Laravel) corriendo como php-fpm.
# nginx (en el container del frontend) le llama via FastCGI al puerto 9000.
# Esta imagen NO sirve HTTP por si misma; siempre se pone nginx delante.

FROM php:8.4-fpm

# Paquetes del sistema:
#  - git, unzip, zip, curl : composer + utilidades
#  - libpq-dev             : driver PDO PostgreSQL
#  - libzip-dev            : extension zip de PHP
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        zip \
        curl \
        libpq-dev \
        libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

# Composer copiado desde su imagen oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Dependencias PHP (capa cacheable por composer.lock)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Resto del codigo + autoload optimizado
COPY . .
RUN composer dump-autoload --optimize

# Permisos para Laravel (storage y bootstrap/cache son los unicos que escribe)
RUN chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

# php-fpm escucha en 9000 por defecto
EXPOSE 9000

# Entrypoint: espera a postgres, aplica migraciones la primera vez,
# y luego arranca php-fpm con el comando del CMD
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm"]
