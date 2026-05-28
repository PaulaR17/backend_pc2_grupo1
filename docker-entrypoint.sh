#!/bin/sh
set -e

#esperamos a postgres como mucho 60s (30 intentos de 2s)
intento=0
listo=0
while [ "$intento" -lt 30 ] && [ "$listo" -eq 0 ]; do
    if php -r "new PDO('pgsql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; then
        listo=1
    else
        sleep 2
    fi
    intento=$((intento+1))
done

if [ "$listo" -eq 0 ]; then
    echo "[entrypoint] postgres no responde tras 60s"
    exit 1
fi

#migraciones + seeder solo la primera vez (marca en el volumen storage)
if [ ! -f "/var/www/html/storage/.installed" ]; then
    php artisan migrate --force
    php artisan db:seed --force || true
    touch /var/www/html/storage/.installed
fi

exec "$@"
