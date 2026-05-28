#!/bin/sh
# Arranque del container backend.
# Espera a que postgres acepte conexiones, ejecuta migraciones + seeder
# en la primera ejecucion y luego arranca el comando del CMD (php-fpm).

set -e

echo "[entrypoint] esperando a postgres en ${DB_HOST}:${DB_PORT}..."
i=0
while ! php -r "new PDO('pgsql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; do
    i=$((i+1))
    if [ "$i" -ge 30 ]; then
        echo "[entrypoint] postgres no responde tras 60s, abortando"
        exit 1
    fi
    sleep 2
done
echo "[entrypoint] postgres OK"

# Migraciones + seeder solo la primera vez (la marca queda en el volumen)
if [ ! -f "/var/www/html/storage/.installed" ]; then
    echo "[entrypoint] aplicando migraciones..."
    php artisan migrate --force

    echo "[entrypoint] poblando datos iniciales (seeder)..."
    php artisan db:seed --force || true

    touch /var/www/html/storage/.installed
fi

exec "$@"
