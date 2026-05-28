FROM php:8.4-fpm

#paquetes para composer, postgres y PC1
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip zip curl libpq-dev libzip-dev \
        python3 python3-pip python3-venv build-essential libxml2-dev libxslt1-dev \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

#PC1 dentro de la imagen, con su propio venv
RUN git clone --depth 1 https://github.com/MatiuxG/Proyecto-de-Computacion-I.git /opt/pc1 \
    && python3 -m venv /opt/pc1-venv \
    && /opt/pc1-venv/bin/pip install --upgrade pip \
    && /opt/pc1-venv/bin/pip install -r /opt/pc1/requirements.txt

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize

RUN chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm"]
