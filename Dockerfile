#PHP 8.4 (lo exige Symfony 8 en composer.lock) + Python 3 dentro del mismo
#container para que `php artisan predictions:run` pueda ejecutar el script
#de PC1 directamente con exec(), sin SSH ni docker exec entre containers.
FROM php:8.4-cli

#paquetes del sistema:
#  - git, unzip, zip, curl: composer + utilidades
#  - libpq-dev: extension PDO PostgreSQL
#  - libzip-dev: extension zip
#  - python3, pip, venv: para ejecutar PC1 (que vive montado como volumen)
#  - build-essential, libxml2-dev, libxslt1-dev: compilar dependencias Python
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        zip \
        curl \
        libpq-dev \
        libzip-dev \
        python3 \
        python3-pip \
        python3-venv \
        build-essential \
        libxml2-dev \
        libxslt1-dev \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

#composer copiado desde su imagen oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

#instalacion de dependencias PHP (composer install) cacheable por capa
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

#resto del codigo y autoload optimizado
COPY . .
RUN composer dump-autoload --optimize

#permisos para Laravel
RUN chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8000

#el container espera tener PC1 montado en /opt/pc1 (via docker-compose volume).
#al arrancar instalamos pip de PC1 con un venv local del proyecto.
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
