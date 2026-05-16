#!/bin/sh
#script de arranque del container backend. Instala las dependencias de Python
#de PC1 dentro del propio container, ejecuta migraciones y arranca Laravel.

set -e

#1. preparar venv de PC1 si esta montado
if [ -d "/opt/pc1" ] && [ -f "/opt/pc1/requirements.txt" ]; then
    if [ ! -d "/opt/pc1/.venv" ]; then
        echo "[entrypoint] creando venv de PC1..."
        python3 -m venv /opt/pc1/.venv
        /opt/pc1/.venv/bin/pip install --upgrade pip
        /opt/pc1/.venv/bin/pip install -r /opt/pc1/requirements.txt
    fi
fi

#2. esperar a postgres (max 60s)
echo "[entrypoint] esperando a postgres..."
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

#3. migraciones y seeder en la primera ejecucion.
#APP_KEY y JWT_SECRET los pasamos por docker-compose, no llamamos a key:generate
#(haria falta un .env editable; aqui Laravel los lee directamente del entorno).
if [ ! -f "/var/www/html/.installed" ]; then
    echo "[entrypoint] aplicando migraciones..."
    php artisan migrate --force

    echo "[entrypoint] poblando datos iniciales (seeder)..."
    php artisan db:seed --force || true

    touch /var/www/html/.installed
fi

#4. arrancar el comando que toque (por defecto el php artisan serve del CMD)
exec "$@"
