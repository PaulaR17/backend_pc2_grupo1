# Instalación en LORCA (con Docker)

Guía para desplegar **PC1 (ML)** y **PC2 (Laravel + Angular + PostgreSQL)**
dentro de la MV de LORCA (IP `10.151.50.151`) usando **Docker**.

Todo el sistema se levanta con un único comando: `docker compose up -d --build`.

---

## 0. Cómo conectarse a LORCA

Con la VPN de la uni activa, desde el terminal de tu portátil:

```bash
ssh grupo1@10.151.50.151
# (te pedirá la contraseña del usuario grupo1)
```

Una vez dentro, todos los comandos se ejecutan en la MV. Para salir, `exit` o `Ctrl+D`.

Para editar archivos directamente en LORCA usa `nano`:

```bash
nano /home/grupo1/proyecto/backend/.env
# Edita, Ctrl+O Enter para guardar, Ctrl+X para salir.
```

---

## 1. Requisitos mínimos en la MV

```bash
docker --version          # >= 20.10
docker compose version    # >= 2.0
```

Si no están instalados:

```bash
sudo apt-get update
sudo apt-get install -y docker.io docker-compose-plugin
sudo usermod -aG docker grupo1
# Reinicia la sesión SSH para que el grupo docker tenga efecto
exit
ssh grupo1@10.151.50.151
```

---

## 2. Clonar los 3 repos en LORCA

```bash
mkdir -p /home/grupo1/proyecto
cd /home/grupo1/proyecto

# PC1 (ML) - rama PCII
git clone -b PCII https://github.com/MatiuxG/Proyecto-de-Computacion-I.git

# PC2 backend
git clone https://github.com/PaulaR17/backend_pc2_grupo1.git backend

# PC2 frontend (mantén la subcarpeta porque el docker-compose la espera así)
mkdir -p frontend
git clone https://github.com/PaulaR17/frontend_pc2_grupo1.git frontend/frontend_pc2_grupo1
```

Estructura final esperada:

```
/home/grupo1/proyecto/
├── Proyecto-de-Computacion-I/        ← PC1 (rama PCII)
├── backend/                          ← Laravel (aquí está docker-compose.yml)
└── frontend/frontend_pc2_grupo1/     ← Angular
```

---

## 3. Configurar el `.env` de PC1 (AEMET key)

PC1 necesita su `.env` con la API key de AEMET (para descargar datos de clima):

```bash
cd /home/grupo1/proyecto/Proyecto-de-Computacion-I
cp .env.example .env
nano .env
```

Pega tu AEMET_API_KEY. El resto de la BD lo pasa el docker-compose, no hace falta tocar.

---

## 4. Configurar el `.env` del backend

```bash
cd /home/grupo1/proyecto/backend
cp .env.example .env
nano .env
```

Lo único crítico que tiene que existir es `APP_KEY` (Laravel lo genera con
`php artisan key:generate`). El docker-entrypoint lo crea automáticamente
la primera vez, así que puedes dejarlo vacío al principio.

Para que docker-compose pueda pasar la AEMET key a PC1, también añade en
el directorio del backend un fichero `.env` con (puede ser el mismo):

```
AEMET_API_KEY=eyJhbGciOiJIUzI1...
```

---

## 5. Levantar todo con Docker

```bash
cd /home/grupo1/proyecto/backend
docker compose up -d --build
```

La primera vez tarda **15-20 minutos** porque descarga las imágenes base
(PHP, Python, Node, nginx, postgres) y construye las tres imágenes.

Verifica que los 3 containers están corriendo:

```bash
docker compose ps
```

Debe mostrar `pc2_postgres`, `pc2_backend` y `pc2_frontend` como `running`.

---

## 6. Entrenar PC1 y generar las primeras predicciones

Las migraciones y el seeder se aplican automáticamente al arrancar el backend.
Pero las **predicciones requieren entrenar los modelos primero**. Esto se hace
una sola vez después de levantar el sistema (tarda ~30-60 min por la descarga
del tráfico histórico):

```bash
docker compose exec backend /opt/pc1/.venv/bin/python /opt/pc1/run_all.py
```

Cuando termine (verás `[OK] Pipeline terminado.`), genera las predicciones:

```bash
docker compose exec backend /opt/pc1/.venv/bin/python /opt/pc1/generar_predicciones.py --dias 7 --target Accidentes
```

Debe terminar con `[OK] 147 predicciones insertadas`.

---

## 7. Verificar que todo el flujo funciona

Desde tu local con navegador:

1. Abre `http://10.151.50.151` → debe cargar el frontend Angular.
2. Login como admin (`admin@ecotraffic.com` / `password123`).
3. Dashboard admin → botón **"Ejecutar predicciones"**.
4. Vuelve a `/home` → botón **"Ver predicciones"** → ver círculos de
   colores sobre los 21 distritos.

Si algo falla, mira los logs:

```bash
docker compose logs -f backend
docker compose logs -f frontend
docker compose logs -f postgres
```

---

## 8. Mantener actualizadas las predicciones (CRON en LORCA)

Sin cron, las predicciones se quedan congeladas. Con cron:

```bash
mkdir -p /home/grupo1/proyecto/logs
crontab -e
```

Pega al final del crontab:

```cron
# === EcoTraffic - regenerar predicciones diariamente a las 04:00 ===
0 4 * * * docker compose -f /home/grupo1/proyecto/backend/docker-compose.yml exec -T backend /opt/pc1/.venv/bin/python /opt/pc1/generar_predicciones.py --dias 7 --target Accidentes >> /home/grupo1/proyecto/logs/predict.log 2>&1

# === EcoTraffic - re-scrape y reentrenamiento mensual (dia 1 a las 03:00) ===
0 3 1 * * docker compose -f /home/grupo1/proyecto/backend/docker-compose.yml exec -T backend /opt/pc1/.venv/bin/python /opt/pc1/run_all.py >> /home/grupo1/proyecto/logs/retrain.log 2>&1
```

Guarda y sal (`Ctrl+O`, `Enter`, `Ctrl+X`).

Comprueba que el cron quedó:

```bash
crontab -l
tail -f /home/grupo1/proyecto/logs/predict.log
```

---

## 9. Comandos útiles del día a día

```bash
# Estado de los containers
docker compose ps

# Ver logs en vivo (Ctrl+C para salir, no para los containers)
docker compose logs -f backend

# Reiniciar un container
docker compose restart backend

# Parar todo
docker compose down

# Parar todo Y borrar volúmenes (¡destruye la BD!)
docker compose down -v

# Entrar dentro del container backend
docker compose exec backend bash

# Forzar reconstrucción tras cambiar Dockerfile
docker compose up -d --build --force-recreate
```

---

## 10. Actualizar el código sin perder la BD

Cuando hayas hecho cambios en GitHub y quieras desplegar la nueva versión:

```bash
ssh grupo1@10.151.50.151
cd /home/grupo1/proyecto/backend && git pull
cd /home/grupo1/proyecto/frontend/frontend_pc2_grupo1 && git pull
cd /home/grupo1/proyecto/Proyecto-de-Computacion-I && git pull origin PCII

cd /home/grupo1/proyecto/backend
docker compose up -d --build
```

Los datos de PostgreSQL se conservan en el volumen `pgdata` y no se borran
salvo que uses `docker compose down -v`.

---

## 11. Cosas que NO se suben a git

Cada repo tiene su `.gitignore` configurado:

**PC1** (`Proyecto-de-Computacion-I/.gitignore`):
- `modelos_guardados/` → se regeneran con `run_all.py`
- `Resultados/` → se regenera con `main.py`
- `.cache_zips/` → ~1GB de ZIPs de tráfico, se rebajan
- `.env` → secretos

**Backend** (`.gitignore`):
- `vendor/` → composer install lo recrea
- `.env` → secretos
- `storage/logs/`

**Frontend** (`.gitignore`):
- `node_modules/` → npm install lo recrea
- `dist/` → npm run build lo recrea
- `.angular/` → cache de build
